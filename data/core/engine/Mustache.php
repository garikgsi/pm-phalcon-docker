<?
namespace Core\Engine;

use Core\Extender\MustacheLoaderWithMinifier;
use Core\Handler\MinifyHtml;
use Phalcon\DI;
use Phalcon\Mvc\View\Engine\AbstractEngine;
use Phalcon\Mvc\View\EngineInterface;
/**
 * Phalcon\Mvc\View\Engine\Mustache
 * Adapter to use Mustache library as templating engine
 */
class Mustache extends AbstractEngine
{
	/**
	 * @var \Mustache_Engine
	 */
	protected $mustache;

	/**
	 * Class constructor.
	 *
	 * @param \Phalcon\Mvc\ViewInterface $view
	 * @param \Phalcon\DiInterface $dependencyInjector
	 */
	public function __construct($view, $dependencyInjector = null)
	{
		$this->mustache = new \Mustache_Engine([
            'partials_loader' => new MustacheLoaderWithMinifier('../core/views/partials/', ['extension' => '.hbs']),
            'cache'   => '../cache/templates/backend/partial/',
            'charset' => 'UTF-8'
        ]);

		parent::__construct($view, $dependencyInjector);
	}
	/**
	 * {@inheritdoc}
	 *
	 * @param string $path
	 * @param array $params
	 * @param boolean $mustClean
	 */
	public function render($path, $params, $mustClean = false)
	{
		if (!isset($params['content'])) {
			$params['content'] = $this->_view->getContent();
		}

//		Пример создания события в Mustache
//		if ($this->getEventsManager()->fire('mustache:testEvent', $this)) {
//			return false;
//		}

		$content = $this->mustache->render(file_get_contents($path), $params);

		if ($mustClean) {
			$this->_view->setContent($content);
		} else {
			echo $content;
		}
	}



    private static $tmplStack = [];
    private static $tmplMustache = false;

    public static function renderWithBinds($path, $params = [], $binds = []) {
        if (!self::$tmplMustache)
            self::$tmplMustache = new \Mustache_Engine([
                'partials_loader' => new MustacheLoaderWithMinifier('../core/views/partials/', ['extension' => '.hbs']),
                'cache'   => '../cache/templates/backend/partial/',
                'charset' => 'UTF-8'
            ]);


        $mustache = self::$tmplMustache;

        $params['SYSBinds'] = $binds;

        // Перехват стэка вызванных шаблонов
        if (isset(self::$tmplStack[$path]))
            return $mustache->render(self::$tmplStack[$path], $params);

        $DI = DI::getDefault();

        $cViewPath = '../core/views/';
        $cacheDir  = '../cache/templates/backend/partial/';

        $cacheFileName = md5($path);

        // Попытка получения шаблона из кеша
        /*if (is_readable($cacheDir.$cacheFileName.'.hbs')) {
            $content = file_get_contents($cacheDir.$cacheFileName.'.hbs');
            self::$tmplStack[$path] = $content;

            return $mustache->render(self::$tmplStack[$path], $params);
        }*/

        $templateFile = $cViewPath.'partials/'.$path.'.hbs';

        //die($templateFile);

        if (!is_readable($templateFile)) {
            $templateFile = preg_replace('/^(.*)views\//', $cViewPath, $templateFile);

            if (!is_readable($templateFile))
                die('there is no such template - '.$templateFile);
        }

        $content = file_get_contents($templateFile);
        $content = preg_replace("#<!%([-_a-zA-Z]+)%!>#miu", '{{{SYSBinds.\\1}}}', $content);
        //$content = (new MinifyHtml($content))->process();

        file_put_contents($cacheDir.$cacheFileName.'.hbs', $content);

        self::$tmplStack[$path] = $content;

        return $mustache->render(self::$tmplStack[$path], $params);
    }

    public static function getTemplate($path) {
        if (!self::$tmplMustache)
            self::$tmplMustache = new \Mustache_Engine([
                'partials_loader' => new MustacheLoaderWithMinifier('../core/views/partials/', ['extension' => '.hbs']),
                'cache'   => '../cache/templates/backend/partial/',
                'charset' => 'UTF-8'
            ]);


        // Перехват стэка вызванных шаблонов
        if (isset(self::$tmplStack[$path]))
            return self::$tmplStack[$path];

        $DI = DI::getDefault();

        $cViewPath = '../core/views/';
        $cacheDir  = '../cache/templates/backend/partial/';

        $cacheFileName = md5($path);

        // Попытка получения шаблона из кеша
        if (is_readable($cacheDir.$cacheFileName.'.hbs')) {
            $content = file_get_contents($cacheDir.$cacheFileName.'.hbs');
            self::$tmplStack[$path] = $content;

            return self::$tmplStack[$path];
        }

        $templateFile = $cViewPath.'partials/'.$path.'.hbs';

        if (!is_readable($templateFile)) {
            $templateFile = preg_replace('/^(.*)view\//', $cViewPath, $templateFile);

            if (!is_readable($templateFile))
                die('there is no such template - '.$templateFile);
        }

        $content = file_get_contents($templateFile);
        //$content = (new MinifyHtml($content))->process();

        file_put_contents($cacheDir.$cacheFileName.'.hbs', $content);

        self::$tmplStack[$path] = $content;

        return self::$tmplStack[$path];
    }
}