<?php
namespace Core\Handler;


class OpenGraph
{
    private static $instance = null;

    private $prefixes = [
        'og' => ''
    ];

    private $title = '';
    private $type  = 'website';
    private $url   = '';
    private $description = '';
    private $site_name   = '';

    private $images = [];

    private $article = [
        'published_time' => '',
        'author'  => '',
        'section' => '',
        'tag'     => ''
    ];

    private $profile = [
        'first_name' => '',
        'last_name'  => '',
        'gender'     => '',
        'username'   => ''
    ];

    private $map = [
        'type',
        'title',
        'description',
        'url',
        'site_name'
    ];


    public function __construct(Site $SiteHandler) {
        if (self::$instance) {
            return self::$instance;
        }

        self::$instance = $this;

        $langName = $SiteHandler->getLangName();
        $siteId = $SiteHandler->getId();

        $OGTCFile = DIR_CACHE_SITES.$siteId.'_ogtc_meta_'.$langName.'.php';

        $siteOGTC = is_file($OGTCFile) ? require($OGTCFile) : 0;


        if ($siteOGTC && (isset($siteOGTC['facebook_large']) || isset($siteOGTC['facebook_small']))) {

            if (isset($siteOGTC['facebook_large'])) {
                list($width, $height) = explode('x', $siteOGTC['facebook_large']['icon_size']);

                $this->setImage($siteOGTC['facebook_large']['icon_uri'], $width, $height);
            }
            else {
                list($width, $height) = explode('x', $siteOGTC['facebook_small']['icon_size']);

                $this->setImage($siteOGTC['facebook_small']['icon_uri'], $width, $height);

            }


            $this->setTitle($siteOGTC['title']);
            $this->setDescription($siteOGTC['description']);
            $this->setSiteName($siteOGTC['og_site']);
        }

        $this->setUrl('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

        return $this;
    }

    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    public function setTitle($title) {
        $title = trim($title);

        $this->title = $title != '' ? $title : $this->title;

        return $this;
    }

    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    public function setSiteName($siteName) {
        $this->site_name = $siteName;

        return $this;
    }

    public function setImage($url, $width, $height) {
        $this->images[0] = $this->prepareImage('https://'.$_SERVER['HTTP_HOST'].$url, $width, $height);

        return $this;
    }

    public function setArticle($author, $publishedTime, $section = '', $tag = '') {
        $this->prefixes['article'] = '/article';

        $this->article['author'] = $author;
        $this->article['published_time'] = $publishedTime;
        $this->article['section'] = $section;
        $this->article['tag']     = $tag;

        return $this;
    }

    public function setProfile($username, $gender = '', $firstName = '', $lastName = '') {
        $this->prefixes['profile'] = '/profile';

        $this->profile['first_name'] = $firstName;
        $this->profile['last_name']  = $lastName;
        $this->profile['gender']     = $gender;
        $this->profile['username']   = $username;

        return $this;
    }

    public function addImage($url, $width, $height) {
        $this->images[] = $this->prepareImage($url, $width, $height);

        return $this;
    }



    public function generateHtmlPrefixes() {
        $content = [];

        foreach($this->prefixes as $i => $v) {
            $content[] = $i.': https://ogp.me/ns'.$v.'#';
        }

        return ' prefix="'.implode(' ', $content).'"';
    }

    public function generateHtmlMeta() {
        $content = '';

        for($i = 0, $len = sizeof($this->map); $i < $len; $i++) {
            $propName = $this->map[$i];
            $propVal  = trim($this->$propName);

            if (!$propVal) {
                continue;
            }

            $content .= $this->metaTag($propName, $propVal);
        }

        for($i = 0, $len = sizeof($this->images); $i < $len; $i++) {
            $img = $this->images[$i];

            $content .= $this->metaTag('image',        $img['url']   );
            $content .= $this->metaTag('image:type',   $img['type']  );
            $content .= $this->metaTag('image:width',  $img['width'] );
            $content .= $this->metaTag('image:height', $img['height']);
        }

        $additionalSections = $this->prefixes;

        unset($additionalSections['og']);

        foreach($additionalSections as $i => $v) {
            $sectionData = $this->$i;

            foreach($sectionData as $i2 => $v2) {
                if ($v2 == '') {
                    continue;
                }

                $content .= $this->metaTag($i2, $v2, $i);
            }
        }

        return $content;
    }

    private function prepareImage($url, $width, $height) {
        $urlExplode = explode('.', $url);
        $type = $urlExplode[sizeof($urlExplode) - 1];
        $typeExpl = explode('?', $type);

        return [
            'url'    => $url,
            'type'   => 'image/'.$typeExpl[0],
            'width'  => $width,
            'height' => $height
        ];
    }

    private function metaTag($name, $content, $type = 'og') {
        return '<meta property="'.$type.':'.$name.'" content="'.$content.'" />';

    }
}