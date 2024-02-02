<?php
namespace Core\Builder;

use Phalcon\Di\Injectable;

/**
 * @property \Core\Handler\User user
 * @property \Core\Handler\Site site
 * @property \Core\Handler\SocketIO socketIO
 * @property \Core\Handler\HistoryApi hapi
 * @property \Core\Handler\OpenGraph openGraph
 * @property \Core\Handler\TwitterCards twitterCards
 */
class Html extends Injectable
{
    private $htmlClasses = ['elzTheme-dark', 'scrollHideFine'];

    public function generateHtmlAttributes() {
        $attributesForHTMLTag = '';

        $attributesForHTMLTag .= $this->getHtmlClasses();
        $attributesForHTMLTag .= $this->getUserAttributes();
        $attributesForHTMLTag .= $this->getSiteAttributes();
        $attributesForHTMLTag .= $this->getSocketIOAttribute();
        $attributesForHTMLTag .= $this->getOpenGraphAttribute();

        return $attributesForHTMLTag;
    }

    private function getUserAttributes() {
        return ' data-device="'.$this->user->getDevice().'" data-user="'.$this->user->getEncodedData().'"';
    }

    private function getSiteAttributes() {
        $languageAttribute = ' lang="'.$this->site->getLangName().'"';
        $templatesVersion  = ' data-template="'.$this->site->getTemplatesVersion().'"';
        $javascriptVersion = ' data-js="'.$this->site->getJavascriptVersion().'"';
        $minificationEnabled = ' data-compress="'.($this->site->isMinificationEnabled() ? 1 : 0).'"';

        return $languageAttribute.$templatesVersion.$javascriptVersion.$minificationEnabled;
    }

    private function getSocketIOAttribute() {
        $sessionName = '';//$this->socketIO->getSessionName();

        return $sessionName ? ' data-session="'.$sessionName.'"' : '';
    }

    private function getOpenGraphAttribute() {
        return $this->openGraph->generateHtmlPrefixes();
    }

    public function generateMetaTags() {
        $metaTagsHtml = '';

        $metaTagsHtml .= $this->getSiteMetaHtml();
        $metaTagsHtml .= $this->getHistoryApiHtml();
        $metaTagsHtml .= $this->getOpenGraphMetaHtml();
        $metaTagsHtml .= $this->getTwitterCardsMetaHtml();

        return $metaTagsHtml;
    }

    private function getSiteMetaHtml() {
        $siteId = $this->site->getId();
        $langName = $this->site->getLangName();

        $meta = $this->site->getMeta();
        $metaData = $this->site->getMetaHtml();

        $html  = '<meta charset="utf-8" />';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
        $html .= '<meta name="format-detection" content="telephone=no" />';

        $html .= '<meta id="ipEnSiteId" name="cnf" data-id="'.$siteId.'"/>';
        $html .= '<meta id="ipEnSiteKeywords" name="keywords" content="'.$meta['keywords'].'"/>';

        $html .= '<meta id="ipEnSiteDescription" name="description" content="'.$meta['description'].'" />';

        // Разрешает применение манифеста для имитации приложения
        $html .= '<meta name="mobile-web-app-capable" content="yes" />';

        // Разрешает применение для эппла
        $html .= '<meta name="apple-mobile-web-app-capable" content="yes" />';

        // Имя приложения в плитке windows 8-10\
        if (isset($metaData['name'])) {
            $html .= '<meta name="application-name" content="'.$metaData['name'].'" />';
        }

        // Цвет плитки приложения в windows 8-10. !!! В XML ФАЙЛЕ ЭТОТ ПАРАМЕТР НЕ РАБОТАЕТ !!!
        if (isset($metaData['tile_color'])) {
            $html .= '<meta name="msapplication-TileColor" content="'.$metaData['tile_color'].'" />';
        }

        // Цвет браузерной строки в хроме
        if (isset($metaData['addr_chrome'])) {
            $html .= '<meta name="theme-color" content="'.$metaData['addr_chrome'].'" />';
        }

        $html .= '<meta name="msapplication-config" content="/meta/'.$siteId.'_browserconfig.xml" />'; // Конфиг с иконками для windows 8-10 -->

        // Имя приложения в списке приложений ИОСа
        if (isset($metaData['name'])) {
            $html .= '<meta name="apple-mobile-web-app-title" content="'.$metaData['name'].'" />';
        }

        // Цвет строки браузера сафари
        if (isset($metaData['addr_safari'])) {
            $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="'.$metaData['addr_safari'].'" />';
        }

        // Ссылка на картинку фона для экрана загрузки приложения
        if (isset($metaData['apple_splash'])) {
            $icon1024 = $metaData['apple_splash'];
            $html .= '<link rel="apple-touch-startup-image" href="'.$icon1024['icon_uri'].'" />';
        }

        // Иконка для девайсов Apple
        if (isset($metaData['apple_touch'])) {
            $icon180 = $metaData['apple_touch'];
            $html .= '<link rel="apple-touch-icon" sizes="'.$icon180['icon_size'].'" href="'.$icon180['icon_uri'].'" />';
        }

        // Набор иконок для десктопных браузеров и некоторых мобильных браузеров.
        // Также используется яндексом при выведении результатов поиска
        if (isset($metaData['ico'])) {
            $html .= '<link rel="icon" type="image/x-icon" sizes="'.$metaData['ico'].'" href="/uploads/icos/'.$siteId.'_ico_result.ico" />';
        }

        // Иконка для андроида, используется также в манифесте для добавления приложения на рабочий стол
        if (isset($metaData['android'])) {
            $icon192 = $metaData['android'];
            $html .= '<link rel="icon" type="'.$icon192['icon_type'].'" sizes="'.$icon192['icon_size'].'" href="'.$icon192['icon_uri'].'" />';
        }

        // Стандартная иконка для табов в сафари десктопа и хроме на мобильниках
        if (isset($metaData['icon_32'])) {
            $icon32 = $metaData['icon_32'];
            $html .= '<link rel="icon" type="'.$icon32['icon_type'].'" sizes="'.$icon32['icon_size'].'" href="'.$icon32['icon_uri'].'" />';
        }

        // Стандартная PNG иконка для десктоп браузеров (преимущественно используется фаирфоксом, такая же как и 16х16 в формате .ico
        if (isset($metaData['icon_16'])) {
            $icon16 = $metaData['icon_16'];
            $html .= '<link rel="icon" type="'.$icon16['icon_type'].'" sizes="'.$icon16['icon_size'].'" href="'.$icon16['icon_uri'].'" />';
        }

        // СВГ маска для закладок в сафари и для тачбара макбуков
        if (isset($metaData['apple_mask'])) {
            $icon170 = $metaData['apple_mask'];
            $html .= '<link rel="mask-icon" href="'.$icon170['icon_uri'].'"'.(isset($metaData['touch_color']) ? 'color="'.$metaData['touch_color'].'"' : ''). '/>';
        }

        // Манифест для приложения андроида. !!! В МАНИФЕСТЕ ЗНАЧЕНИЯ BACKGROUND и THEME_COLOR ДОЛЖНЫ СОВПАДАТЬ,
        // ЧТОБЫ ТРЕЙ И БЭКГРАУНД ЗАГРУЗОЧНОГО ЭКРАНА ПРИЛОЖЕНИЯ СМОТРЕЛИСЬ В ОДНОМ ЦВЕТ
        $html .= '<link rel="manifest" href="/meta/'.$siteId.'_manifest_'.$langName.'.json">';

        $html .= '<title>'.$meta['title'].'</title>';

        return $html;
    }

    private function getHistoryApiHtml() {
        $meta = $this->site->getMeta();

        $title = $meta['title'];

        $site = $this->hapi->getSite();
        $controller = $this->hapi->getController();
        $action = $this->hapi->getAction();

        return '<meta id="ipEnJSControllersAction" name="hapi-name" content="'.$title.'" data-site="'.$site.'" data-controller="'.$controller.'" data-action="'.$action.'" />';
    }

    private function getOpenGraphMetaHtml() {
        return $this->openGraph->generateHtmlMeta();
    }

    private function getTwitterCardsMetaHtml() {
        return $this->twitterCards->generateHtmlMeta();
    }

    public function generateResourcesCSS() {

    }

    public function generateResourcesJS() {

    }

    public function addHtmlClass($className) {
        $this->htmlClasses[] = $className;
    }

    private function getHtmlClasses() {
        if (!sizeof($this->htmlClasses)) {
            return '';
        }

        return ' class="'.implode(' ', $this->htmlClasses).'"';
    }
}