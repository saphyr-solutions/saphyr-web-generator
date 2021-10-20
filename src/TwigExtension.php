<?php
namespace SaphyrWebGenerator;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
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
        $return[] = new TwigFunction('dump', 'dump');
        $return[] = new TwigFunction('asset', [$this,"assetFunction"]);

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
    public function assetFunction($asset)
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
            $return = '/' . $this->saphyrWebGenerator->api->getMedia($asset);
        }
        return $return;
    }

    /**
     * @param $asset
     * @return string|null
     */
    public function snl2brFunction($string)
    {
        $return = $string;
        $isHtml = $string !== strip_tags($string);
        if (!$isHtml) {
            $return = nl2br($string);
        }
        return $return;
    }
}