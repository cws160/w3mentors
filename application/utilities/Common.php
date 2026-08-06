<?php

/**
 * A Common Utility Class 
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Common
{

    /**
     * Home Page Slides Above Footer
     * 
     * @param FatTemplate $template
     */
    public static function homePageSlidesAboveFooter(FatTemplate $template)
    {
        $srch = Testimonial::getSearchObject(MyUtility::getSiteLangId(), true);
        $srch->addMultipleFields(['t.*', 't_l.testimonial_text']);
        $srch->addCondition('testimoniallang_testimonial_id', 'is not', 'mysql_func_null', 'and', true);
        $srch->addOrder('testimonial_added_on', 'desc');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $template->set("testimonials", $records);
    }

    /**
     * Blog Side Panel Area
     * 
     * @param FatTemplate $template
     */
    public static function blogSidePanelArea(FatTemplate $template)
    {
        $siteLangId = MyUtility::getSiteLangId();
        $blogSrchFrm = static::getBlogSearchForm();
        $blogSrchFrm->setFormTagAttribute('action', MyUtility::makeUrl('Blog'));
        /* to fill the posted data into form[ */
        $postedData = FatApp::getPostedData();
        $blogSrchFrm->fill($postedData);
        /* ] */
        /* Right Side Categories Data[ */
        $categoriesArr = BlogPostCategory::getParentChilds($siteLangId);
        $template->set('categoriesArr', $categoriesArr);
        /* ] */
        $template->set('blogSrchFrm', $blogSrchFrm);
        $template->set('siteLangId', $siteLangId);
    }

    /**
     * Get Blog Search Form
     * 
     * @return Form
     */
    public static function getBlogSearchForm(): Form
    {
        $frm = new Form('frmBlogSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->setFormTagAttribute('autocomplete', 'off');
        $frm->addTextBox('', 'keyword', '', ['placeholder' => Label::getLabel('Lbl_Search')]);
        $frm->addHiddenField('', 'page', 1);
        $frm->addSubmitButton('', 'btn_submit', '');
        return $frm;
    }

    /**
     * Get Header Search Form
     * 
     * @return Form
     */
    public static function getheaderSearchForm(FatTemplate $template)
    {
        $frm = new Form('frmHomeSearch');
        $frm->addTextBox('', 'keyword');
        $frm->addHiddenField('', 'type', AppConstant::FILTER_ALL);
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        $template->set('frm', $frm);
    }

    /**
     * Does String Start With
     * 
     * @param string $string
     * @param string $piece
     * @return bool
     */
    public static function doesStringStartWith(string $string, string $piece): bool
    {
        return mb_substr($string, 0, strlen($piece), 'utf-8') == $piece;
    }

    /**
     * Get Uri From Path
     * 
     * @param type $path
     * @return type
     */
    public static function getUriFromPath($path)
    {
        return self::doesStringStartWith($path, CONF_WEBROOT_URL) ? rtrim(substr($path, strlen(CONF_WEBROOT_URL)), '/') : ltrim($path, '/');
    }

    /**
     * Get File Type Icon
     * 
     * @param type $fileName
     * @return string
     */
    public static function getFileTypeIcon($fileName)
    {
        $svg = '';
        $imgExtensions = Afile::getAllowedExts(Afile::TYPE_USER_PROFILE_IMAGE);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $extension = in_array($extension, $imgExtensions) ? 'img' : $extension;

        switch ($extension) {
            case 'img':
                $svg = '<svg class="icon icon--small icon--image" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 11.1l2-2 5.5 5.5 3.5-3.5 3 3V5H5v6.1zm0 2.829V19h3.1l2.986-2.985L7 11.929l-2 2zM10.929 19H19v-2.071l-3-3L10.929 19zM4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm11.5 7a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"></path></svg>';
                break;
            case 'pdf':
                $svg = '<svg class="icon icon--small icon--pdf" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 16H8V8h4a4 4 0 1 1 0 8zm-2-6v4h2a2 2 0 1 0 0-4h-2zm5-6H5v16h14V8h-4V4zM3 2.992C3 2.444 3.447 2 3.999 2H16l5 5v13.993A1 1 0 0 1 20.007 22H3.993A1 1 0 0 1 3 21.008V2.992z"></path></svg>';
                break;
            case 'doc':
            case 'docx':
                $svg = '<svg class="icon icon--small icon--doc" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 8v8h-2l-2-2-2 2H8V8h2v5l2-2 2 2V8h1V4H5v16h14V8h-3zM3 2.992C3 2.444 3.447 2 3.999 2H16l5 5v13.993A1 1 0 0 1 20.007 22H3.993A1 1 0 0 1 3 21.008V2.992z"></path></svg>';
                break;
            case 'ppt':
            case 'pptx':
                $svg = '<svg class="icon icon--small icon--ppt" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 2.992C3 2.444 3.447 2 3.999 2H16l5 5v13.993A1 1 0 0 1 20.007 22H3.993A1 1 0 0 1 3 21.008V2.992zM5 4v16h14V8h-3v6h-6v2H8V8h7V4H5zm5 6v2h4v-2h-4z"></path></svg>';
                break;
            case 'txt':
                $svg = '<svg class="icon icon--small icon--text" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 8v12.993A1 1 0 0 1 20.007 22H3.993A.993.993 0 0 1 3 21.008V2.992C3 2.455 3.449 2 4.002 2h10.995L21 8zm-2 1h-5V4H5v16h14V9zM8 7h3v2H8V7zm0 4h8v2H8v-2zm0 4h8v2H8v-2z"></path></svg>';
                break;
            case 'zip':
                $svg = '<svg class="icon icon--small icon--zip" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M10.414 3l2 2H21a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h7.414zM18 18h-4v-3h2v-2h-2v-2h2V9h-2V7h-2.414l-2-2H4v14h16V7h-4v2h2v2h-2v2h2v5z"></path>
            </svg>';
                break;
            default:
                $svg = '<svg class="icon icon--small icon--attachment" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14.828 7.757l-5.656 5.657a1 1 0 1 0 1.414 1.414l5.657-5.656A3 3 0 1 0 12 4.929l-5.657 5.657a5 5 0 1 0 7.071 7.07L19.071 12l1.414 1.414-5.657 5.657a7 7 0 1 1-9.9-9.9l5.658-5.656a5 5 0 0 1 7.07 7.07L12 16.244A3 3 0 1 1 7.757 12l5.657-5.657 1.414 1.414z"></path></svg>';
                break;
        }
        return $svg;
    }

    /**
     * Function to overwrite css basic vars with selected theme colors
     *
     * @param boolean $isDashboard
     * @return html
     */
    public static function setThemeColorStyle($isDashboard = false)
    {
        /* check if request is for preview then get preview theme id otherwise load default theme */
        $themeId = isset($_SESSION['preview_theme']) ? $_SESSION['preview_theme'] : FatApp::getConfig('CONF_ACTIVE_THEME');
        $themeData = Theme::getAttributesById($themeId, ['theme_primary_color', 'theme_primary_inverse_color', 'theme_secondary_inverse_color', 'theme_secondary_color', 'theme_footer_inverse_color', 'theme_footer_color', 'theme_gradient_primary_color', 'theme_gradient_secondary_color', 'theme_borders_style']);

        /* set default css variables for theme updates */
        $themeColorStyle = '<style>
        :root {
            --color-primary: #' . $themeData['theme_primary_color'] . ";\n"
            . "\t\t\t" . '--color-secondary: #' . $themeData['theme_secondary_color'] . ";\n"
            . "\t\t\t" . '--color-primary-inverse: #' . (!empty($themeData['theme_primary_inverse_color']) ? $themeData['theme_primary_inverse_color'] : 'ffffff') . ";\n"
            . "\t\t\t" . '--color-secondary-inverse: #' . (!empty($themeData['theme_secondary_inverse_color']) ? $themeData['theme_secondary_inverse_color'] : 'ffffff') . ";\n"
            . "\t\t\t" . '--color-dark-blue: #' . $themeData['theme_footer_color'] . ";\n"
            . "\t\t\t" . '--color-dark-blue-inverse: #' . (!empty($themeData['theme_footer_inverse_color']) ? $themeData['theme_footer_inverse_color'] : 'ffffff') . ";\n"
            . "\t\t\t" . '--color-gradient-1: #' . (!empty($themeData['theme_gradient_primary_color']) ? $themeData['theme_gradient_primary_color'] : 'ffffff') . ";\n"
            . "\t\t\t" . '--color-gradient-2: #' . (!empty($themeData['theme_gradient_secondary_color']) ? $themeData['theme_gradient_secondary_color'] : 'ffffff') . ";\n";

        if ($themeData['theme_borders_style'] == AppConstant::THEME_BUTTONS_SHARP) {
            /* business theme */
            $themeColorStyle .= "\t\t\t--radius: 0px;\n"
                . "\t\t\t--radius-sm: 0px;\n"
                . "\t\t\t--radius-md: 0px;\n"
                . "\t\t\t--radius-lg: 0px;\n"
                . "\t\t\t--radius-xl: 0px;\n"
                . "\t\t\t--radius-xxxl: 0px;\n";
        } elseif ($themeData['theme_borders_style'] == AppConstant::THEME_BUTTONS_ROUNDED) {
            /* online tutoring theme */
            $themeColorStyle .= "\t\t\t--radius: 12px;\n"
                . "\t\t\t--radius-sm: 8px;\n"
                . "\t\t\t--radius-md: 16px;\n"
                . "\t\t\t--radius-lg: 20px;\n"
                . "\t\t\t--radius-xl: 24px;\n"
                . "\t\t\t--radius-xxxl: 32px;\n";
        } elseif ($themeData['theme_borders_style'] == AppConstant::THEME_BUTTONS_PILLS) {
            /* elearning theme */
            $themeColorStyle .= "\t\t\t--radius: 24px;\n"
                . "\t\t\t--radius-sm: 12px;\n"
                . "\t\t\t--radius-md: 24px;\n"
                . "\t\t\t--radius-lg: 30px;\n"
                . "\t\t\t--radius-xl: 70px;\n"
                . "\t\t\t--radius-xxxl: 40px;\n";
        } elseif ($themeData['theme_borders_style'] == AppConstant::THEME_BUTTONS_LEAF) {
            /* wellness theme */
            $themeColorStyle .= "\t\t\t--radius: 24px 0;\n"
                . "\t\t\t--radius-sm: 8px 0;\n"
                . "\t\t\t--radius-md: 16px 0;\n"
                . "\t\t\t--radius-lg: 20px 0;\n"
                . "\t\t\t--radius-xl: 24px 0;\n"
                . "\t\t\t--radius-xxxl: 32px 0;\n";
        }


        $themeColorStyle .= '}' . "\n" . "\t";

        if ($isDashboard == true) {
            $themeColorStyle .= 'html[data-theme="dashboard-secondary"], .dashboard-learner {
                --color-primary: #' . $themeData['theme_secondary_color'] . ';
                --color-primary-inverse: #' . (!empty($themeData['theme_secondary_inverse_color']) ? $themeData['theme_secondary_inverse_color'] : 'FFFFFF') . ';
                --color-secondary: #' . $themeData['theme_primary_color'] . ';
                --color-secondary-inverse:  #' . (!empty($themeData['theme_primary_inverse_color']) ? $themeData['theme_primary_inverse_color'] : 'FFFFFF') . ';
            }
            ';
        }
        $themeColorStyle .= "</style>\n";
        return $themeColorStyle;
    }
}
