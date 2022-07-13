<?php
namespace SaphyrWebGenerator;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var SaphyrWebGenerator
     */
    protected $saphyrWebGenerator;

    /**
     * @var FilesystemLoader
     */
    protected $twigLoader;

    /**
     * @param SaphyrWebGenerator $saphyrWebGenerator
     */
    public function __construct(SaphyrWebGenerator $saphyrWebGenerator, FilesystemLoader $twigLoader)
    {
        $this->saphyrWebGenerator = $saphyrWebGenerator;
        $this->twigLoader = $twigLoader;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $return = parent::getFunctions();

        // Define dump function
//        $return[] = new TwigFunction('dump', [$this,"dumpFunction"]);
        $return[] = new TwigFunction('asset', [$this,"assetFunction"]);
        $return[] = new TwigFunction('isHTML', [$this,"isHTML"]);
        $return[] = new TwigFunction('href', [$this,"href"]);

        return $return;
    }

    public function getFilters()
    {
        $return = parent::getFilters();

        $return[] = new TwigFilter('snl2br', [$this,"snl2brFunction"]);

        return $return;
    }


    /**
     * @return array
     */
    public function getGlobals(): array
    {
        return [
            "_post" => $_POST,
            "_get" => $_GET,
            "_session" => $_SESSION,
        ];
    }

    /**
     * @param $asset
     * @return string|null
     */
    public function assetFunction($asset,$params=null)
    {
        $return = null;
        if (is_string($asset)) {
            // Load asset from loader dirs
            foreach ($this->twigLoader->getPaths() as $path) {
                if (file_exists($path . '/' . $asset)) {
                    $return = '/' . $path . '/' . $asset;
                    break;
                }
            }
        } elseif (is_array($asset) && isset($asset["id"])) {
            // Load file from API
			if(isset($params)) {
				if(!is_array($params)) $params=[];
				$allowed  = ['width', 'height'];
				$filtered = array_filter(
					$params,
					function ($key) use ($allowed) {
						return in_array($key, $allowed);
					},
					ARRAY_FILTER_USE_KEY);
			}
            $return = '/' . $this->saphyrWebGenerator->api->getMedia($asset,$params);
        }
        return $return;
    }

	public function href($string) {
        return SaphyrWebGenerator::getHref($string);
	}

	public function isHTML($string) {
		return $string != strip_tags($string) ? true:false;
	}

    /**
     * @param $asset
     * @return string|null
     */
    public function snl2brFunction($string)
    {
        $return = $string;
        $addBr = strpos($string, '<br>') === false && strpos($string, '<br />') === false && strpos($string, '<br/>') === false;
        if ($addBr) {
            $return = nl2br($string);
        }
        return $return;
    }

    /**
     * @param $asset
     * @return string|null
     */
    public function dumpFunction(...$vars)
    {
        foreach ($vars as $var) {
            dump($var);
        }
    }
}