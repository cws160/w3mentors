<?php

/**
 * A Common Translator Class 
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Translator extends FatModel
{

    /**
     * Table's fields with values as associative array
     * 
     * @var array
     */
    private $textList = [];

    /**
     * All site language code as associative array
     *
     * @var array
     */
    private $langCodes = [];

    /**
     * Table name in which to update text fields
     *
     * @var string|null
     */
    private $table = null;

    /**
     * the language code of input text
     *
     * @var string|null
     */
    private $from = null;

    /**
     * the language codes into which text will be translated
     *
     * @var array|null
     */
    private $to = [];

    /**
     * fromLangId variable
     *
     * @var int|null
     */
    private $fromLangId = null;

    /**
     * single Lang Translate
     *
     * @var bool
     */
    private $singleLangTranslate = false;

    /**
     * formated text fileds after translated
     *
     * @var array
     */
    private $formatedFields = [];

    /**
     * Initialize Translator
     *
     * @param integer|null $fromLangId
     * @param array|null $toLangIds
     */
    public function __construct(int $fromLangId = null, array $toLangIds = null)
    {
        parent::__construct();
        if (!self::isTranslatorActive()) {
            $this->error = Label::getLabel('LBL_TRANSLATOR_NOT_ACTIVE');
            return false;
        }
        $this->setFromAndTo($fromLangId, $toLangIds);
    }

    /**
     * check translator is active or not
     *
     * @return boolean
     */
    public static function isTranslatorActive(): bool
    {
        $translator = new MicrosoftTranslator();
        return $translator->init();
    }

    /**
     * Set from and to languages 
     *
     * @param integer|null $fromLangId
     * @param array|null $toLangIds
     * @return void
     */
    private function setFromAndTo(int $fromLangId = null, array $toLangIds = null)
    {
        if (is_null($fromLangId)) {
            $fromLangId = FatApp::getConfig('CONF_DEFAULT_LANG');
        }
        $this->fromLangId = $fromLangId;
        $this->langCodes = $languages = Language::getCodes();
        $this->from = $languages[$fromLangId] ?? null;
        unset($languages[$fromLangId]);
        $this->to = (empty($toLangIds)) ? $languages : array_intersect_key($languages, array_flip($toLangIds));
    }

    /**
     * Translate the text data
     *
     * @param string $table
     * @param array $data
     * @param integer|string $recordId
     * @return boolean
     */
    public function translate(string $table, array $data, $recordId, bool $update = true): bool
    {
        if (empty($data)) {
            $this->error = Label::getLabel('LBL_EMPTY_DATA');
            return false;
        }
        $this->table = $table;
        if (!$this->formatedData($data)) {
            return false;
        }
        if (!$data = $this->getTranslatedText()) {
            return false;
        }
        $data = $this->formatTranslatedFields($data);
        if ($update) {
            return $this->updateContent($data, $recordId);
        }
        return true;
    }

    /**
     * Update the Translated Content in the database
     *
     * @param array $translatedContent
     * @param integer|string  $recordId
     * @param array $otherFileds
     * @return boolean
     */
    private function updateContent(array $translatedContent, $recordId, array $otherFileds = []): bool
    {
        foreach ($translatedContent as $langId => $fields) {
            if (!$primaryFields = $this->getPrimaryFields($recordId, $langId, $otherFileds)) {
                return false;
            }
            $record = new TableRecord($this->table);
            $record->assignValues(array_merge($fields, $primaryFields));
            if (!$record->addNew([], $record->getFlds())) {
                $this->error = $record->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Translate and Auto fill text
     *
     * @param string $table
     * @param string|int $recordId
     * @return bool
     */
    public function translateAndAutoFill(string $table, $recordId, array $data = [])
    {
        if (empty($this->from) || empty($this->to)) {
            $this->error = Label::getLabel('LBL_FROM_AND_TO_IS_REQUIRED');
            return false;
        }
        if ($table == User::DB_TBL_LANG) {
            $data = $this->getTextFromDb($recordId, $table);
            return $this->translate($table, $data, $recordId, false);
        } elseif ($table == GroupClass::DB_TBL_LANG) {
            return $this->translateGroupClassOrPackageText($table, $recordId);
        } elseif ($table == ContentPage::DB_TBL_LANG) {
            return $this->translateContentPageText($recordId, $this->getContentPageText($recordId), false);
        } elseif ($table == Configurations::DB_TBL) {
            return $this->translateConfigurationsText($this->getConfigurationText(), false);
        } elseif ($table == BlogPost::DB_LANG_TBL) {
            return $this->translateBlogPostText($recordId, $this->getTextFromDb($recordId, $table), false);
        } elseif ($table == Label::DB_TBL) {
            return $this->translateLabelText($recordId, $data);
        } elseif ($table == AppLabel::DB_TBL) {
            return $this->translateAppLabelText($recordId, $data);
        } elseif ($table == CertificateTemplate::DB_TBL) {
            $data = $this->getTextFromDb($recordId, $table);
            return $this->translateCertificateText($recordId, $data, false);
        } elseif ($table == PageLangData::DB_TBL) {
            $data = $this->getTextFromDb($recordId, $table);
            return $this->translatePageLangText($recordId, $data, false);
        } else {
            $data = $this->getTextFromDb($recordId, $table);
            return $this->translate($table, $data, $recordId, false);
        }
        return true;
    }

    /**
     * validate and Translate
     *
     * @param string $table
     * @param string|integer $recordId
     * @param array $post
     * @return void
     */
    public function validateAndTranslate(string $table, $recordId, array $post)
    {
        if (empty($post['update_langs_data'])) {
            return true;
        }
        if (empty($this->from) || empty($this->to)) {
            $this->error = Label::getLabel('LBL_FROM_AND_TO_IS_REQUIRED');
            return false;
        }
        $this->table = $table;
        switch ($table) {
            case GroupClass::DB_TBL_LANG:
            case User::DB_TBL_LANG:
                return $this->translate($table, $post, $recordId);
            case ContentPage::DB_TBL_LANG:
                return $this->translateContentPageText($recordId, $post);
            case ExtraPage::DB_TBL_LANG:
            case Faq::DB_TBL_LANG:
                $this->singleLangTranslate = true;
                return $this->translate($table, $post, $recordId);
            case Configurations::DB_TBL:
                return $this->translateConfigurationsText($post);
            case BlogPost::DB_LANG_TBL:
                return $this->translateBlogPostText($recordId, $post);
            case Category::DB_LANG_TBL:
                return $this->translateCategoryText($recordId, $post);
            case CertificateTemplate::DB_TBL:
                return $this->translateCertificateText($recordId, $post);
            case PageLangData::DB_TBL:
                return $this->translatePageLangText($recordId, $post);
            default:
                return $this->translate($table, $post, $recordId);
        }
    }

    /**
     * Translate content page text
     *
     * @param int $recordId
     * @param array $post
     * @return boolean
     */
    private function translateContentPageText(int $recordId, array $post, bool $update = true): bool
    {
        if (!$this->translate(ContentPage::DB_TBL_LANG, $post, $recordId, $update)) {
            return false;
        }
        $this->singleLangTranslate = true;
        //if (!empty($post['cpage_layout'])) {
        if ($post['cpage_layout'] == ContentPage::CONTENT_PAGE_LAYOUT2_TYPE) {
            $this->textList = [];
            $this->textList['cpage_content'] = $post['cpage_content'] ?? '';
            if (!$data = $this->getTranslatedText()) {
                return false;
            }
            $data = $this->formatTranslatedFields($data);
            if ($update) {
                return $this->updateContent($data, $recordId);
            }
        } else {
            $this->table = ContentPage::DB_TBL_CONTENT_PAGES_BLOCK_LANG;
            for ($i = 1; $i <= ContentPage::CONTENT_PAGE_LAYOUT1_BLOCK_COUNT; $i++) {
                $this->textList = [];
                $this->textList['cpblocklang_text'] = $post['cpblock_content_block_' . $i] ?? '';
                $post['cpblocklang_block_id'] = $i;
                if (!$data = $this->getTranslatedText()) {
                    return false;
                }
                $data = $this->formatTranslatedFields($data);
                foreach ($data as $langId => $fields) {
                    $this->formatedFields[$langId]['cpblock_content_block_' . $i] = $fields['cpblocklang_text'];
                }
                if ($update && !$this->updateContent($data, $recordId, $post)) {
                    return false;
                }
            }
        }
        //}
        return true;
    }

    /**
     * Translate Configurations text
     *
     * @param array $post
     * @return boolean
     */
    private function translateConfigurationsText(array $post, bool $update = true): bool
    {
        if (empty($post['CONF_WEBSITE_NAME_' . $this->fromLangId] ?? '')) {
            $this->error = Label::getLabel('LBL_WEBSITE_NAME_IS_EMPTY');
            return false;
        }
        $this->textList = [
            'CONF_WEBSITE_NAME' => $post['CONF_WEBSITE_NAME_' . $this->fromLangId] ?? '',
            'CONF_FROM_NAME' => $post['CONF_FROM_NAME_' . $this->fromLangId] ?? '',
            'CONF_ADDRESS' => $post['CONF_ADDRESS_' . $this->fromLangId] ?? '',
            'CONF_COOKIES_TEXT' => $post['CONF_COOKIES_TEXT_' . $this->fromLangId] ?? ''
        ];
        if (!$translatedContent = $this->getTranslatedText()) {
            return false;
        }
        $fieldsIndex = array_keys($this->textList);
        $updateFields = [];
        foreach ($translatedContent as $langCode => $translatedText) {
            $langCode = strtolower($langCode);
            $langId = array_search($langCode, $this->langCodes);
            if ($langId === false) {
                continue;
            }
            foreach ($translatedText as $index => $text) {
                $updateFields[$fieldsIndex[$index] . '_' . $langId] = $text;
                $this->formatedFields[$langId][$fieldsIndex[$index] . '_' . $langId] = $text;
            }
        }
        
        if (!$update) {
            return true;
        }
        $record = new Configurations();
        if (!$record->update($updateFields)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     *  Translate Blog post text
     *
     * @param integer $recordId
     * @param array $post
     * @return boolean
     */
    private function translateBlogPostText(int $recordId, array $post, bool $update = true): bool
    {
        if (empty($post['post_title'] ?? '')) {
            $this->error = Label::getLabel('LBL_POST_TITLE_IS_EMPTY');
            return false;
        }
        $this->textList = [
            'post_title' => $post['post_title'] ?? '',
            'post_author_name' => $post['post_author_name'] ?? '',
        ];
        if (!$data = $this->getTranslatedText()) {
            return false;
        }
        $data = $this->formatTranslatedFields($data);
        if ($update && !$this->updateContent($data, $recordId)) {
            return false;
        }
        $fields = ['post_description' => $post['post_description'] ?? '',];
        $this->singleLangTranslate = true;
        foreach ($fields as $key => $value) {
            $this->textList = [];
            $this->textList[$key] = $value;
            if (!$data = $this->getTranslatedText()) {
                return false;
            }
            $data = $this->formatTranslatedFields($data);
            if ($update && !$this->updateContent($data, $recordId, $post)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Translate Page Lang Data
     *
     * @param string $recordId
     * @param array $post
     * @param boolean $update
     * @return bool
     */
    public function translatePageLangText(string $recordId, array $post, bool $update = true)
    {
        if (empty($post['plang_title'] ?? '')) {
            $this->error = Label::getLabel('LBL_PAGE_TITLE_IS_EMPTY');
            return false;
        }
        $this->textList = [
            'plang_title' => $post['plang_title'] ?? '',
            'plang_summary' => $post['plang_summary'] ?? '',
            'plang_warring_msg' => $post['plang_warring_msg'] ?? '',
            'plang_recommendations' => $post['plang_recommendations'] ?? '',
            'plang_helping_text' => $post['plang_helping_text'] ?? '',
        ];
        if (!$data = $this->getTranslatedText()) {
            return false;
        }
        $data = $this->formatTranslatedFields($data);

        if ($update && !$this->updateContent($data, $recordId)) {
            return false;
        }
        return true;
    }

    public function translateCategoryText(int $recordId, array $post)
    {
        $this->table = Category::DB_LANG_TBL;
        $this->textList = [
            'cate_name' => $post['cate_name'] ?? '',
            'cate_details' => $post['cate_details'] ?? '',
        ];
        if (!$data = $this->getTranslatedText()) {
            return false;
        }
        $srch = new SearchBase(Category::DB_LANG_TBL);
        $srch->addCondition('catelang_cate_id', '=', $recordId);
        $srch->addCondition('catelang_lang_id', '!=', $post['catelang_lang_id']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'catelang_lang_id');
        $data = $this->formatTranslatedFields($data);
        foreach ($data as $langId => $fields) {
            if (isset($records[$langId])) {
                $data[$langId]['catelang_id'] = $records[$langId]['catelang_id'];
            }
        }
        if (!$this->updateContent($data, $recordId)) {
            return false;
        }
        return true;
    }

    public function translateCertificateText(string $recordId, array $post, bool $update = true)
    {
        $this->table = CertificateTemplate::DB_TBL;
        $this->textList = json_decode($post['certpl_body'], true);
        if (!$data = $this->getTranslatedText()) {
            return false;
        }
        $srch = new SearchBase($this->table);
        $srch->addCondition('certpl_code', '=', $recordId);
        $srch->addCondition('certpl_lang_id', '!=', $post['certpl_lang_id']);
        $srch->addMultipleFields(['certpl_lang_id', 'certpl_code']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'certpl_lang_id');
        $data = $this->formatTranslatedFields($data);
        $array = [];
        foreach ($data as $langId => $fields) {
            if (isset($records[$langId])) {
                $array[$langId] = [
                    'certpl_lang_id' => $records[$langId]['certpl_lang_id'],
                    'certpl_code' => $records[$langId]['certpl_code'],
                    'certpl_body' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ];
            }
        }
        if ($update && !$this->updateContent($array, $recordId)) {
            return false;
        }
        return true;
    }

    /**
     * Translate label text
     *
     * @param string $recordId
     * @param array $post
     * @return void
     */
    public function translateLabelText(string $recordId, array $post)
    {
        if (!$this->translate(Label::DB_TBL, $post, $recordId, false)) {
            return false;
        }
        foreach ($this->formatedFields as $langId => &$value) {
            $value['label_caption' . $langId] = $value['label_caption'];
            unset($value['label_caption']);
        }
        return true;
    }

    /**
     * Translate app label text
     *
     * @param string $recordId
     * @param array $post
     * @return void
     */
    public function translateAppLabelText(string $recordId, array $post)
    {
        if (!$this->translate(AppLabel::DB_TBL, $post, $recordId, false)) {
            return false;
        }
        foreach ($this->formatedFields as $langId => &$value) {
            $value['applbl_value' . $langId] = $value['applbl_value'];
            unset($value['applbl_value']);
        }
        return true;
    }

    /**
     * get Text from database
     *
     * @param integer|string $recordId
     * @return array
     */
    private function getTextFromDb($recordId, $table = null): array
    {
        $this->table = $table;
        if (!$primaryFields = $this->getPrimaryFields($recordId, $this->fromLangId)) {
            return [];
        }
        $searchBase = new SearchBase($this->table);
        foreach ($primaryFields as $field => $value) {
            $searchBase->addCondition($field, '=', $value);
        }
        $searchBase->doNotCalculateRecords();
        $searchBase->setPageSize(1);
        $data = FatApp::getDb()->fetch($searchBase->getResultSet());
        return (empty($data)) ? [] : $data;
    }

    /**
     * Get Content Page Text From DB
     *
     * @param integer $pageId
     * @return array
     */
    private function getContentPageText(int $pageId): array
    {
        $contentPage = ContentPage::getAllAttributesById($pageId);
        $langData = ContentPage::getAttributesByLangId($this->fromLangId, $pageId);
        if (!empty($langData)) {
            $blockData = ContentPage::getPageBlocksContent($pageId, $this->fromLangId);
            foreach ($blockData as $blockKey => $blockContent) {
                $langData['cpblock_content_block_' . $blockKey] = $blockContent['cpblocklang_text'];
            }
            return array_merge($langData, $contentPage);
        }
        return $contentPage;
    }

    /**
     * get Configuration Text From DB
     *
     * @return array
     */
    private function getConfigurationText(): array
    {
        return Configurations::getConfigurations([
            'CONF_WEBSITE_NAME_' . $this->fromLangId,
            'CONF_FROM_NAME_' . $this->fromLangId,
            'CONF_ADDRESS_' . $this->fromLangId,
            'CONF_COOKIES_TEXT_' . $this->fromLangId
        ]);
    }

    /**
     * Format the post data 
     *
     * @param array $post
     * @return boolean
     */
    private function formatedData(array $post): bool
    {
        switch ($this->table) {
            case Preference::DB_TBL_LANG:
                if (empty($post['prefer_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList['prefer_title'] = $post['prefer_title'];
                break;
            case SpeakLanguage::DB_TBL_LANG:
                if (empty($post['slang_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList['slang_name'] = $post['slang_name'];
                break;
            case TeachLanguage::DB_TBL_LANG:
                if (empty($post['tlang_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList['tlang_name'] = $post['tlang_name'];
                $this->textList['tlang_description'] = $post['tlang_description'];
                break;
            case IssueReportOptions::DB_TBL_LANG:
                if (empty($post['tissueoptlang_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList['tissueoptlang_title'] = $post['tissueoptlang_title'];
                break;
            case ContentPage::DB_TBL_LANG:
                if (empty($post['cpage_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['cpage_title' => $post['cpage_title']];
                if (!empty($post['cpage_layout']) && $post['cpage_layout'] == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
                    $this->textList['cpage_image_title'] = $post['cpage_image_title'] ?? '';
                }
                break;
            case ExtraPage::DB_TBL_LANG:
                if (empty($post['epage_label'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['epage_label' => $post['epage_label'], 'epage_content' => $post['epage_content'] ?? ''];
                break;
            case Navigations::DB_TBL_LANG:
                if (empty($post['nav_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['nav_name' => $post['nav_name']];
                break;
            case NavigationLinks::DB_TBL_LANG:
                if (empty($post['nlink_caption'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['nlink_caption' => $post['nlink_caption']];
                break;
            case Country::DB_TBL_LANG:
                if (empty($post['country_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['country_name' => $post['country_name']];
                break;
            case VideoContent::DB_TBL_LANG:
                if (empty($post['biblecontentlang_biblecontent_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['biblecontentlang_biblecontent_title' => $post['biblecontentlang_biblecontent_title']];
                break;
            case Testimonial::DB_TBL_LANG:
                if (empty($post['testimonial_text'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['testimonial_text' => $post['testimonial_text']];
                break;
            case FaqCategory::DB_LANG_TBL:
                if (empty($post['faqcat_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['faqcat_name' => $post['faqcat_name']];
                break;
            case Faq::DB_TBL_LANG:
                if (empty($post['faq_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['faq_title' => $post['faq_title'], 'faq_description' => $post['faq_description'] ?? ''];
                break;
            case EmailTemplates::DB_TBL:
                if (empty($post['etpl_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['etpl_name' => $post['etpl_name'], 'etpl_subject' => $post['etpl_subject'] ?? '', 'etpl_body' => $post['etpl_body'] ?? '', 'etpl_status' => $post['etpl_status'] ?? 1];
                break;
            case Coupon::DB_TBL_LANG:
                if (empty($post['coupon_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['coupon_title' => $post['coupon_title'], 'coupon_description' => $post['coupon_description'] ?? ''];
                break;
            case Currency::DB_TBL_LANG:
                if (empty($post['currency_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['currency_name' => $post['currency_name']];
                break;
            case BlogPostCategory::DB_TBL_LANG:
                if (empty($post['bpcategory_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['bpcategory_name' => $post['bpcategory_name']];
                break;
            case BlogPostCategory::DB_TBL_LANG:
                if (empty($post['bpcategory_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['bpcategory_name' => $post['bpcategory_name']];
                break;
            case MetaTag::DB_LANG_TBL:
                if (empty($post['meta_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = [
                    'meta_title' => $post['meta_title'], 'meta_keywords' => $post['meta_keywords'] ?? '',
                    'meta_description' => $post['meta_description'] ?? '', 'meta_og_title' => $post['meta_og_title'] ?? '',
                    'meta_og_description' => $post['meta_og_description'] ?? ''
                ];
                break;
            case Label::DB_TBL:
                if (empty($post['label_caption' . $this->fromLangId] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['label_caption' => $post['label_caption' . $this->fromLangId]];
                break;
            case AppLabel::DB_TBL:
                if (empty($post['applbl_value' . $this->fromLangId] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['applbl_value' => $post['applbl_value' . $this->fromLangId]];
                break;
            case User::DB_TBL_LANG:
                if (empty($post['user_biography'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['user_biography' => $post['user_biography']];
                break;
            case GroupClass::DB_TBL_LANG:
                if (empty($post['grpcls_title'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList = ['grpcls_title' => $post['grpcls_title'], 'grpcls_description' => $post['grpcls_description'] ?? ''];
                break;
            case Category::DB_LANG_TBL:
                $this->textList = ['cate_name' => $post['cate_name'] ?? '', 'cate_details' => $post['cate_details'] ?? ''];
                break;
            case CourseLanguage::DB_TBL_LANG:
                $this->textList = ['clang_name' => $post['clang_name'] ?? ''];
                break;
            case State::DB_TBL_LANG:
                $this->textList = ['state_name' => $post['state_name'] ?? ''];
                break;
            case SubscriptionPlan::DB_TBL_LANG:
                $this->textList = ['subplang_subplan_title' => $post['subplang_subplan_title'] ?? ''];
                break;
            case ForumReportIssueReason::DB_TBL_LANG:
                if (empty($post['frireason_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList['frireason_name'] = $post['frireason_name'];
                break;
            case SpeakLanguageLevel::DB_TBL_LANG:
                if (empty($post['slanglvl_name'] ?? '')) {
                    $this->error = Label::getLabel('LBL_EMPTY_DATA');
                    return false;
                }
                $this->textList['slanglvl_name'] = $post['slanglvl_name'];
                break;
            default:
                $this->error = Label::getLabel('LBL_INVALID_TABLE');
                return false;
                break;
        }
        return true;
    }

    /**
     * get the Primary fields for updated the and save the language data 
     *
     * @param integer|string $recordId
     * @param integer $langId
     * @param array $otherFileds 
     * @return array|bool
     */
    private function getPrimaryFields($recordId, int $langId, array $otherFileds = [])
    {
        switch ($this->table) {
            case Preference::DB_TBL_LANG:
                return ['preferlang_prefer_id' => $recordId, 'preferlang_lang_id' => $langId];
            case SpeakLanguage::DB_TBL_LANG:
                return ['slanglang_slang_id' => $recordId, 'slanglang_lang_id' => $langId];
            case TeachLanguage::DB_TBL_LANG:
                return ['tlanglang_tlang_id' => $recordId, 'tlanglang_lang_id' => $langId];
            case IssueReportOptions::DB_TBL_LANG:
                return ['tissueoptlang_tissueopt_id' => $recordId, 'tissueoptlang_lang_id' => $langId];
            case ContentPage::DB_TBL_LANG:
                return ['cpagelang_cpage_id' => $recordId, 'cpagelang_lang_id' => $langId];
            case ContentPage::DB_TBL_CONTENT_PAGES_BLOCK_LANG:
                return ['cpblocklang_lang_id' => $langId, 'cpblocklang_cpage_id' => $recordId, 'cpblocklang_block_id' => $otherFileds['cpblocklang_block_id']];
            case ExtraPage::DB_TBL_LANG:
                return ['epagelang_lang_id' => $langId, 'epagelang_epage_id' => $recordId];
            case Navigations::DB_TBL_LANG:
                return ['navlang_lang_id' => $langId, 'navlang_nav_id' => $recordId];
            case NavigationLinks::DB_TBL_LANG:
                return ['nlinklang_lang_id' => $langId, 'nlinklang_nlink_id' => $recordId];
            case Country::DB_TBL_LANG:
                return ['countrylang_lang_id' => $langId, 'countrylang_country_id' => $recordId];
            case VideoContent::DB_TBL_LANG:
                return ['biblecontentlang_lang_id' => $langId, 'biblecontentlang_biblecontent_id' => $recordId];
            case Testimonial::DB_TBL_LANG:
                return ['testimoniallang_lang_id' => $langId, 'testimoniallang_testimonial_id' => $recordId];
            case FaqCategory::DB_LANG_TBL:
                return ['faqcatlang_lang_id' => $langId, 'faqcatlang_faqcat_id' => $recordId];
            case Faq::DB_TBL_LANG:
                return ['faqlang_lang_id' => $langId, 'faqlang_faq_id' => $recordId];
            case EmailTemplates::DB_TBL:
                return ['etpl_lang_id' => $langId, 'etpl_code' => $recordId];
            case Coupon::DB_TBL_LANG:
                return ['couponlang_lang_id' => $langId, 'couponlang_coupon_id' => $recordId];
            case Currency::DB_TBL_LANG:
                return ['currencylang_lang_id' => $langId, 'currencylang_currency_id' => $recordId];
            case BlogPostCategory::DB_TBL_LANG:
                return ['bpcategorylang_lang_id' => $langId, 'bpcategorylang_bpcategory_id' => $recordId];
            case BlogPost::DB_LANG_TBL:
                return ['postlang_lang_id' => $langId, 'postlang_post_id' => $recordId];
            case MetaTag::DB_LANG_TBL:
                return ['metalang_lang_id' => $langId, 'metalang_meta_id' => $recordId];
            case Label::DB_TBL:
                return ['label_lang_id' => $langId, 'label_key' => $recordId];
            case AppLabel::DB_TBL:
                return ['applbl_lang_id' => $langId, 'applbl_key' => $recordId];
            case User::DB_TBL_LANG:
                return ['userlang_user_id' => $recordId, 'userlang_lang_id' => $langId];
            case GroupClass::DB_TBL_LANG:
                return ['gclang_grpcls_id' => $recordId, 'gclang_lang_id' => $langId];
            case Category::DB_LANG_TBL:
                return ['catelang_cate_id' => $recordId, 'catelang_lang_id' => $langId];
            case CourseLanguage::DB_TBL_LANG:
                return ['clanglang_clang_id' => $recordId, 'clanglang_lang_id' => $langId];
            case CertificateTemplate::DB_TBL:
                return ['certpl_code' => $recordId, 'certpl_lang_id' => $langId];
            case State::DB_TBL_LANG:
                return ['stlang_state_id' => $recordId, 'stlang_lang_id' => $langId];
            case SubscriptionPlan::DB_TBL_LANG:
                return ['subplang_subplan_id' => $recordId, 'subplang_lang_id' => $langId];
            case ForumReportIssueReason::DB_TBL_LANG:
                return ['frireasonlang_frireason_id' => $recordId, 'frireasonlang_lang_id' => $langId];
            case PageLangData::DB_TBL:
                return ['plang_key' => $recordId, 'plang_lang_id' => $langId];
            case SpeakLanguageLevel::DB_TBL_LANG:
                return ['slanglvllang_slanglvl_id' => $recordId, 'slanglvllang_lang_id' => $langId];
            default:
                $this->error = Label::getLabel('LBL_INVALID_TABLE');
                return false;
        }
    }

    /**
     * Add translator actions button
     *
     * @param Form $frm
     * @param integer $langId
     * @param integer|string $recordId
     * @param string $tableName
     * @return void
     */
    public static function addTranslatorActions(Form $frm, int $langId, $recordId, string $tableName, int $defaultLangId = 0)
    {
        $fld = null;
        if (self::isTranslatorActive() && count(Language::getCodes()) > 1) {
            if (!$defaultLangId) {
                $defaultLangId = Language::getDefaultLang();
            }
            if ($langId == $defaultLangId) {
                $frm->addCheckBox(Label::getLabel('LBL_AUTO_TRANSLATE_FOR_OTHER_LANGUAGES', $langId), 'update_langs_data', AppConstant::YES, [], false, AppConstant::NO);
            } else {
                $onClick = "translateAndAutoFill('" . $tableName . "', '" . $recordId . "', " . $langId . ")";
                if (in_array($tableName, [Label::DB_TBL, AppLabel::DB_TBL])) {
                    $onClick = "autoFillLabel('" . $tableName . "', '" . $recordId . "', this.form)";
                }
                $fld = $frm->addButton('', 'autofill_lang', Label::getLabel('LBL_AUTOFILL_LANGUAGE_DATA', $langId), ["class" => "btn-primary btn-primary-bordered", 'onclick' => $onClick]);
            }
        }
        $submitFld = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES', $langId));
        if (!is_null($fld)) {
            $submitFld->attachField($fld);
        }
        return;
    }

    /**
     *  format translated Fields
     *
     * @param array $translatedContent
     * @return array
     */
    private function formatTranslatedFields(array $translatedContent): array
    {
        $fields = [];
        $fieldsIndex = array_keys($this->textList);
        foreach ($translatedContent as $langCode => $translatedText) {
            $langCode = strtolower($langCode);
            $langId = array_search($langCode, $this->langCodes);
            if ($langId === false) {
                continue;
            }
            foreach ($translatedText as $index => $text) {
                $this->formatedFields[$langId][$fieldsIndex[$index]] = $text;
                $fields[$langId][$fieldsIndex[$index]] = $text;
            }
        }
        return $fields;
    }

    /**
     * Get Translated Text from Third party
     *
     * @return bool|array
     */
    private function getTranslatedText()
    {
        $translator = new MicrosoftTranslator();
        $translator->formatText($this->textList);
        if (!$data = $translator->translate($this->from, $this->to, $this->singleLangTranslate)) {
            $this->error = $translator->getError();
            return false;
        }
        if (!$data = $translator->formatTranslatedContent($data)) {
            $this->error = $translator->getError();
            return false;
        }
        return $data;
    }

    public function getTranslatedFields(): array
    {
        return $this->formatedFields;
    }

    /**
     * Translate Group Class or package text
     * @param string $table
     * @param string $recordId
     * @return bool
     */
    private function translateGroupClassOrPackageText($table, string $recordId)
    {
        $data = $this->getTextFromDb($recordId, $table);

        $textListArray = [
            'grpcls_title' => $data['grpcls_title'] ?? '',
            'grpcls_description' => $data['grpcls_description'] ?? '',
        ];

        $searchBase = new SearchBase(GroupClass::DB_TBL);
        $searchBase->addCondition('grpcls_id', '=', $recordId);

        $searchBase->doNotCalculateRecords();
        $searchBase->setPageSize(1);
        $data = FatApp::getDb()->fetch($searchBase->getResultSet());

        if ($data['grpcls_type'] == GroupClass::TYPE_PACKAGE) {
            $searchBase = new SearchBase(GroupClass::DB_TBL);
            $searchBase->addCondition('grpcls_parent', '=', $recordId);
            $searchBase->doNotCalculateRecords();
            $relatedData = FatApp::getDb()->fetchAll($searchBase->getResultSet());

            if ($relatedData && !empty($relatedData)) {
                foreach ($relatedData as $grpCls) {
                    $data = $this->getTextFromDb($grpCls['grpcls_id'], $table);
                    $textListArray['title[' . $grpCls['grpcls_id'] . ']'] = $data['grpcls_title'] ?? '';
                }
            }
        }

        $this->textList = $textListArray;

        if (!$data = $this->getTranslatedText()) {
            return false;
        }

        $this->formatTranslatedFields($data);
        return true;
    }
}
