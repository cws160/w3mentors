<?php

/**
 * Microsoft Translator
 * 
 * @author Fatbit Technologies
 */
class MicrosoftTranslator extends FatModel
{
    private $subscriptionKey;
    private $textArray = [];
    private $translations = [];
    private $textLength = 0;

    const HOST = 'https://api.cognitive.microsofttranslator.com';
    const TRANSLATE_PATH = '/translate?api-version=3.0';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Initialize Translator Method
     * 
     * @return bool
     */
    public function init(): bool
    {
        $this->subscriptionKey = FatApp::getConfig('CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY');
        if (empty($this->subscriptionKey)) {
            $this->error = Label::getLabel("LBL_CONFIGURATION_NOT_SET");
            return false;
        }
        return true;
    }

    public function translate(string $from, array $to, bool $singleLangTranslate = false)
    {
        if (!$this->init()) {
            return false;
        }
        if (empty($this->textArray)) {
            $this->error = Label::getLabel("LBL_TEXT_IS_REQUIRED");
            return false;
        }
        $this->translations = [];
        if ($singleLangTranslate) {
            if (!$result = $this->exeCurlRequest($from, $to)) {
                return false;
            }
            $this->translations = array_merge($this->translations, array_values($result));
        } else {
            foreach ($to as $lang) {
                if (!$result = $this->exeCurlRequest($from, [$lang])) {
                    return false;
                }
                $this->translations = array_merge($this->translations, array_values($result));
            }
        }
        return $this->translations;
    }

    public function formatText(array $textArray)
    {
        $this->textArray = [];
        foreach ($textArray as $text) {
            array_push($this->textArray, [
                'Text' => $text
            ]);
            $this->textLength += strlen($text);
        }
        return $this->textArray;
    }

    public function formatTranslatedContent()
    {
        $reponse = [];
        foreach ($this->translations as $value) {
            if (empty($value['translations'])) {
                continue;
            }
            foreach ($value['translations'] as $text) {
                if (empty($text['to'])) {
                    continue;
                }
                $reponse[$text['to']][] = $text['text'];
            }
        }
        return $reponse;
    }

    private function comCreateGuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function exeCurlRequest(string $from, array $to)
    {
        $to = '&to=' . strtolower(implode("&to=", $to));
        $content = FatUtility::convertToJson($this->textArray, JSON_UNESCAPED_UNICODE);
        $curl_headers = array(
            'Content-type: application/json',
            'Content-length: ' . strlen($content),
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'X-ClientTraceId: ' . $this->comCreateGuid()
        );
        $region = FatApp::getConfig('CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_REGION', FatUtility::VAR_STRING, '');
        if (!empty($region)) {
            $curl_headers[] = 'Ocp-Apim-Subscription-Region: ' . $region;
        }
        $url = self::HOST . self::TRANSLATE_PATH . $to . "&from=" . $from . "&textType=html";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if (empty($result) || !empty($result['error'])) {
            $this->error = Label::getLabel("LBL_SOMTHING_WENT_WORNG");
            if (!empty($result['error']['message'])) {
                $this->error = $result['error']['message'];
            }
            return false;
        }
        return $result;
    }
}
