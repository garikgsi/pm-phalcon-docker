<?php
namespace Core\Extender;

use Phalcon\Mvc\View;
use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Translate\InterpolatorFactory;
use Phalcon\Translate\TranslateFactory;

/**
 * Данная оболочка добавляет следующие функционалы:
 * 1. Автоматическая установка уровня рендеринга для ХисториАпи запросов по POST флагу render
 * 2. Добавление суб-роутера для локальных API вызовов в рамках контроллера, выполняются через аякс
 * 3. Функция для добавления функций обратного вызова для разных уровней рендеринга ХисториАпи
 *
 * Class ControllerApp
 * @package Core\Extender
 * @property \Core\Handler\HistoryApi hapi

 * @property \Core\Lib\BreadCrumbs breadcrumbs
 * @property \Core\Handler\Site site
 * @property \Core\Handler\OpenGraph openGraph
 * @property \Core\Handler\TwitterCards twitterCards
 */
class ControllerApp extends ControllerSender
{
    public $isAjax = 0; // Является запрос Аяксом

    // Уровни рендеринга, для которых не могут добавлять функции обратного вызова
    public $forbiddenCallbackRenderingLevels = [
        View::LEVEL_NO_RENDER   => true,
        View::LEVEL_MAIN_LAYOUT => true
    ];

    public $langTable = [
        1 => 'ru',
        2 => 'en'
    ];

    protected $t;

    /**
     * В этом инициализаторе мы добавляем особое поведение для управления рендерингом для случаев, когда запрос
     * на обычный экшен прилетает через аякс, тобишь ХисториАпи
     */
    public function initialize() {
        if ($this->request->isAjax()) {
            // по ссылкам с хистори апи, должен прилетать ключ, в котором будет указан уровень рендеринга.
            $renderLevel = (int)$this->request->get('render');

            $this->isAjax = 1; // просто для того, чтобы постоянно не вызывать реквест из аякс

            // если ключ рендеринга не прилетел, то мы отключаем рендеринг вовсе
            $this->view->setRenderLevel($renderLevel ? $renderLevel : View::LEVEL_NO_RENDER);
        }

        $langFilePath = $this->site->getDir().'langs/'.$this->dispatcher->getControllerName().'/'.$this->langTable[$this->user->getLangId()].'.php';

        $interpolator = new InterpolatorFactory();
        $factory      = new TranslateFactory($interpolator);

        $this->view->t = $factory->newInstance(
            'array',
            [
                'content' => array_merge(
                    require_once($langFilePath),
                    require_once($this->site->getDir().'langs/0modules/site_'.$this->langTable[$this->user->getLangId()].'.php')
                )
            ]
        );

        //if (is_file($langFilePath)) {
           /* $this->view->t = new NativeArray([
                'content' => array_merge(
                    require_once($langFilePath),
                    require_once($this->site->getDir().'langs/0modules/site_'.$this->langTable[$this->user->getLangId()].'.php')
                )
            ]);*/

            $this->t = $this->view->t;
        //}
    }

    /**
     * Установка базовых SEO данных по странице
     *
     * @param $seoName
     * @param $contentTitle
     * @param $seoId
     */
    public function setSeoData($seoName, $contentTitle = null, $seoId = null) {
        $imgDir = 'uploads/images/';

        $langId = $this->user->getLangId();

        $sql = $seoId ?
            'SELECT * FROM cnt_seo_list_lang WHERE seo_id   = :src AND lang_id = :lid' :
            'SELECT * FROM cnt_seo_list_lang WHERE seo_name = :src AND lang_id = :lid';

        $seoRow = $this->db->query($sql, ['src' => $seoId ? $seoId : $seoName, 'lid' => $langId])->fetch();

        if (!$seoRow) {
            return;
        }

        $createImgName = function($pref) use ($seoRow) {
            return $seoRow[$pref.'_name'].'_'.$seoRow[$pref.'_id'].'.'.$seoRow[$pref.'_type'];
        };

        if ($seoRow['fb_lr_image_name'] != '') {
            $this->openGraph->setImage($imgDir.$createImgName('fb_lr_image'), 1200, 630);
        }
        else if ($seoRow['fb_sm_image_name'] != '') {
            $this->openGraph->setImage($imgDir.$createImgName('fb_lr_image'), 800, 800);
        }

        if ($seoRow['tw_lr_image_name'] != '') {
            $this->twitterCards->setImage($imgDir.$createImgName('tw_lr_image'));
            $this->twitterCards->setCardSummaryLarge();
        }
        else if ($seoRow['tw_sm_image_name'] != '') {
            $this->twitterCards->setImage($imgDir.$createImgName('tw_lr_image'));
            $this->twitterCards->setCardSummary();
        }

        if ($seoRow['seo_ogtc_title'] != '') {
            $this->twitterCards->setTitle($seoRow['seo_ogtc_title']);
            $this->openGraph->setTitle($seoRow['seo_ogtc_title']);
        }

        if ($seoRow['seo_ogtc_description'] != '') {
            $this->twitterCards->setDescription($seoRow['seo_ogtc_description']);
            $this->openGraph->setDescription($seoRow['seo_ogtc_description']);
        }

        $this->site->setMetaTitle($seoRow['seo_meta_title'] != '' ? $seoRow['seo_meta_title'] : $contentTitle);

        if ($seoRow['seo_meta_keywords'] != '') {
            $this->site->setMetaKeywords($seoRow['seo_meta_keywords']);
        }

        if ($seoRow['seo_meta_description'] != '') {
            $this->site->setMetaKeywords($seoRow['seo_meta_description']);
        }
    }

    /**
     * Если прилетает ссылка вида controller/api[MethodName] мы вызываем специализированные апи методы.
     * Именование методов api[MethodName]()
     *
     * @param string $method - название метода локального апи контроллера
     */
    public function apiAction($method = '') {
        $methodName = 'api'.ucfirst($method); // Делаем первую букву метода заглавной, чтобы круто

        if (/*!$this->isAjax ||*/ !is_callable(array($this, $methodName))) {
            $this->sendResponseByCode(404);
        }


        $this->$methodName();

        die('call send method'); // Подсказка на тот случай, если я забыл вызвать сендер Жисона
    }

    /**
     * Если прилетает ссылка вида controller/api[MethodName] мы вызываем специализированные апи методы.
     * Именование методов api[MethodName]()
     *
     * @param string $method - название метода локального апи контроллера
     */
    public function fluAction($method = '') {
        $methodName = 'flu'.ucfirst($method); // Делаем первую букву метода заглавной, чтобы круто

        if (!$this->request->hasFiles() || !is_callable(array($this, $methodName))) {
            $this->sendResponseByCode(404);
        }

        $this->$methodName();

        die('call send method'); // Подсказка на тот случай, если я забыл вызвать сендер Жисона
    }

    /**
     * Добавление функции обратного вызова для выполнения после ХисториАпи запроса.
     * После того как хистори апи отработает, будут вызваны указанные функции для обработки каких-то дополнительных
     * поведений на стороне клиента.
     *
     * Функция сработает только при Аякс запросе и в том случае, если требуемый уровень рендеринга соотвествует
     * текущему уровню рендеринга системы шаблонизации
     *
     * @param int $attachRenderLevel - уровень рендеринга для которого нужно вызвать эту функцию
     * @param string $functionName  - название вызываемой функции
     * @param string $functionScope - контейнер где хранится функция в джава скрипте
     * @param array $data
     * @return void
     */
    public function attachHapiCallback($attachRenderLevel, $functionName, $functionScope, $data = []) {
        $renderLevel = $this->view->getRenderLevel();

        if (
            !$this->isAjax || // Если не аякс запрос, то это явно не хистори апи
            $renderLevel != $attachRenderLevel || // Если текущий уровень рендера не равен требуемому уровню рендеринга
            isset($this->forbiddenCallbackRenderingLevels[$renderLevel]) // Уровень рендеринга в списке запрещенных
        ) {
            return;
        }

        $this->hapi->addCallback($functionName, $functionScope, $data);
    }

    /**
     * Осуществляет реализацию ошибки 404 в режиме ошибки для апи и редиректа при прямом запросе ссылки
     *
     * @param string $redirectUri - URI для редиректа при возникновении ошибки тогда, когда мы делаем запрос не через api
     */
    public function errorNoPage404($redirectUri = '/') {
        if ($this->isAjax) {
            $this->sendResponseByCode(404);
        }

        $this->redirect($redirectUri);
    }

    public function showError404() {
        $this->hapi->setHapiController('index');
        $this->hapi->setHapiAction('index_404');
        $this->view->pick('index/error404');
        $this->view->setVar('notStandAlone', 1);

        $this->response->setStatusCode(404, 'Not Found');
    }
}