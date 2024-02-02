<?php
namespace Core\Handler;

use Phalcon\Di\Injectable;


class TwitterCards extends Injectable
{
    const LEN_TITLE = 70;
    const LEN_DESCR = 200;
    const LEN_ALT   = 420;

    private static $instance = null;

    private $card  = 'summary';
    private $title = '';
    private $description = '';

    private $site    = ['' => '', 'id'  => null];
    private $creator = ['' => '', 'id'  => null];
    private $image   = ['' => '', 'alt' => null];

    private $modeMap = [
        'summary'             => ['card', 'site', 'creator', 'description', 'title', 'image'],
        'summary_large_image' => ['card', 'site', 'creator', 'description', 'title', 'image']
    ];

    public function __construct(Site $SiteHandler) {
        if (self::$instance) {
            return self::$instance;
        }

        self::$instance = $this;

        $langName = $SiteHandler->getLangName();
        $siteId = $SiteHandler->getId();

        $OGTCFile = DIR_CACHE_SITES.$siteId.'_ogtc_meta_'.$langName.'.php';

        $siteOGTC = is_file($OGTCFile) ? require_once($OGTCFile) : 0;


        if ($siteOGTC && (isset($siteOGTC['twitter_large']) || isset($siteOGTC['twitter_small']))) {
            if ($siteOGTC['twitter_large']['icon_uri']) {
                $this->setCardSummaryLarge();
                $this->setImage($siteOGTC['twitter_large']['icon_uri']);
            }
            else if ($siteOGTC['twitter_small']['icon_uri']) {
                $this->setCardSummary();
                $this->setImage($siteOGTC['twitter_small']['icon_uri']);
            }

            $this->setSite($siteOGTC['twitter_account']);
            $this->setTitle($siteOGTC['title']);
            $this->setDescription($siteOGTC['description']);
        }


        return $this;
    }

    public function setCardSummary() {
        $this->card = 'summary';

        return $this;
    }


    public function setCardSummaryLarge() {
        $this->card = 'summary_large_image';

        return $this;
    }


    public function setTitle($title) {
        $this->title = mb_substr($title, 0, self::LEN_TITLE);

        return $this;
    }

    public function setDescription($description) {
        $this->description = mb_substr($description, 0, self::LEN_DESCR);

        return $this;
    }

    public function setSite($content, $id = null) {
        $this->site = ['' => '@'.$content, 'id' => $id];

        return $this;
    }

    public function setCreator($content, $id = null) {
        $this->creator = ['' => '@'.$content, 'id' => $id];

        return $this;
    }

    public function setImage($content, $alt = null) {
        $this->image = ['' => 'https://'.$_SERVER['HTTP_HOST'].$content, 'alt' => mb_substr($alt, 0, self::LEN_ALT)];

        return $this;
    }

    public function generateHtmlMeta() {
        $mapping = $this->modeMap[$this->card];
        $len     = sizeof($mapping);
        $content = '';

        $num = 0;

        for($i = 0; $i < $len; $i++) {
            $item = $mapping[$i];
            $field = $this->$item;

            if (!$item) {
                continue;
            }

            if (is_array($field)) {
                foreach($field as $idx => $val) {
                    if (!$val) {
                        continue;
                    }

                    $content .= $this->metaTag($item.($idx ? ':'.$idx : ''), $val);

                    $num++;
                }
            }
            else {
                if ($field) {
                    $content .= $this->metaTag($item, $field);

                    $num++;
                }
            }
        }

        return $num > 1 ? $content : '';
    }

    private function metaTag($name, $content) {
        return '<meta name="twitter:'.$name.'" content="'.$content.'" />';
    }
}