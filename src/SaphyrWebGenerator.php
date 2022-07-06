<?php

namespace SaphyrWebGenerator;

use SaphyrWebGenerator\Api\Api;
use SaphyrWebGenerator\Api\QueryBuilder;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class SaphyrWebGenerator
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $request_uri;

    /**
     * @var string
     */
    protected $request_page_type;

    /**
     * @var Api
     */
    public $api;

    /**
     * @var int
     */
    protected $request_page_module_id;

    /**
     * @var int
     */
    protected $page_module_id;

    /**
     * @var int
     */
    protected $section_module_id;

    /**
     * @var int
     */
    protected $bloc_module_id;

    /**
     * @var array
     */
    protected $web;

    /**
     * @param Config $config
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        session_start();
        $this->config = $config;

        if ($this->config->editor_mode == 'sph22') {
            $_SESSION['isEditMode'] = true;
        } else if ($this->config->editor_mode == 'Osph22') {
            $_SESSION['isEditMode'] = false;
        }
        if ($_SESSION['isEditMode']) {
            $this->config->api_ttl = $config->api_ttl = 5;
        }

        $this->api = new Api([
            'client' => $config->api_client,
            'domain' => $config->api_domain,
            'pubKey' => $config->api_public_key,
            'privKey' => $config->api_private_key,
            'user' => $config->api_user_login,
            'secret' => $config->api_user_secret,
            'ttl' => $config->api_ttl,
            'debug' => $config->api_debug,
            'tempStorage' => $config->api_temp_storage,
            'webRoot' => $config->api_temp_storage
        ]);
        $this->api->getToken();


        $this->web = $this->getWeb(true);
        $this->page_module_id = $this->getPageModuleId(true);
        $this->section_module_id = $this->getSectionModuleId(true);
        $this->bloc_module_id = $this->getBlocModuleId(true);
        $this->request_uri = $this->getRequestUri();
        $this->request_page_type = $this->getRequestPageType(true);

    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getRequestUri(): string
    {
        if (!$this->request_uri) {
            $uri = substr($_SERVER["REQUEST_URI"], 1);
            $uri = explode("?", $uri, 2)[0];

            if (!$uri) {
                // find home page uri
                $all = $this->api->getModuleElements($this->getPageModuleId())["results"];
                $pages = $this->filterElements($all, $this->getWeb()["values"]["pages"]);
                if (isset($pages[0])) {
                    $uri = $pages[0]["values"]["url"]["value"];
                }
            }

            $this->request_uri = $uri;
        }

        return $this->request_uri;
    }

    /**
     * @return int
     */
    protected function getRequestPageModuleId()
    {
        return $this->request_page_module_id;
    }

    /**
     * @param bool $fromApi
     * @return string
     * @throws \Exception
     */
    protected function getRequestPageType(bool $fromApi = false): string
    {
        if ($fromApi) {
            $pageType = "pages";
            $pageModuleId = $this->getPageModuleId();

            if (strpos($this->getRequestUri(), "/") !== false) {
                $pageType = explode("/", $this->getRequestUri(), 2)[0];
            }

            if ($pageType !== "pages") {
                // Check if module slug exist
                $exist = false;
                $modules = $this->api->getModulesList();
                foreach ($modules as $module) {
                    if ($module["slug"] === $pageType) {
                        $exist = true;
                        $pageModuleId = $module["id"];
                        break;
                    }
                }
                if (!$exist) {
                    $pageType = "pages";
                }
            }

            $this->request_page_type = $pageType;
            $this->request_page_module_id = $pageModuleId;
        }

        return $this->request_page_type;
    }

    /**
     * @param bool $fromApi
     * @return int
     * @throws \Exception
     */
    private function getPageModuleId(bool $fromApi = false): int
    {
        if ($fromApi) {
            foreach ($this->api->getModuleElementFields($this->config->web_module_id)["results"] as $moduleElementField) {
				if(is_array($moduleElementField) && isset($moduleElementField['config']) && isset($moduleElementField['config']['reference'])) {
                if ($moduleElementField["config"]["reference"] === "pages") {
                    $this->page_module_id = (int)$moduleElementField["config"]["linkedModuleId"];
                    break;
                }
            }
            }
            if (!$this->page_module_id) {
                throw new \Exception("page_module_id not found", 500);
            }
        }
        return $this->page_module_id;
    }

    /**
     * @param bool $fromApi
     * @return int
     * @throws \Exception
     */
    private function getSectionModuleId(bool $fromApi = false): int
    {
        if ($fromApi) {
            foreach ($this->api->getModuleElementFields($this->getPageModuleId())["results"] as $moduleElementField) {
				if(is_array($moduleElementField) && isset($moduleElementField['config']) && isset($moduleElementField['config']['reference'])) {
                if ($moduleElementField["config"]["reference"] === "sections") {
                    $this->section_module_id = (int)$moduleElementField["config"]["linkedModuleId"];
                    break;
                }
            }
            }
            if (!$this->section_module_id) {
                throw new \Exception("section_module_id not found", 500);
            }
        }
        return $this->section_module_id;
    }

    /**
     * @param bool $fromApi
     * @return int
     * @throws \Exception
     */
    private function getBlocModuleId(bool $fromApi = false): int
    {
        if ($fromApi) {
            foreach ($this->api->getModuleElementFields($this->getSectionModuleId())["results"] as $moduleElementField) {
				if(is_array($moduleElementField) && isset($moduleElementField['config']) && isset($moduleElementField['config']['reference'])) {
                if ($moduleElementField["config"]["reference"] === "blocs") {
                    $this->bloc_module_id = (int)$moduleElementField["config"]["linkedModuleId"];
                    break;
                }}

            }
            if (!$this->bloc_module_id) {
                throw new \Exception("bloc_module_id not found", 500);
            }
        }
        return $this->bloc_module_id;
    }

	public function handleXHR() {
		if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST)) {
			$datas = $_POST;
			if (isset($datas['form']) && isset($datas['formId'])) {
				$formModule = $this->api->getModuleInfos($datas['form']);
				$module_forms = array_filter($formModule['module_forms'],function($e) use($datas) {
					return $e['id']==$datas['formId'];
				});
				if($module_forms) {
					$module_forms = array_shift($module_forms);
					unset($module_forms['config']);
					$formConfig = $this->api->getModuleElementformConfigFields($datas['form'], $datas['formId']);
					$result = $this->api->addElement($datas['form'], $datas);
					$return = ['result' => $result ? 'success':'error'];
					$return = array_merge($return, $module_forms);
					echo json_encode($return);
					exit();
				}
			}
		}
	}


	/**
	 * Build sitemap.xml if new content added
	 * @param $context
	 * @return void
	 * @throws \Exception
	 */
	protected function refreshSitemap($context) {


		$lastPageMod='';
		$dates = array_column($context['menu'], 'modification_date');
		if($dates && is_array($dates)) {
			$dates = array_map(function ($e) {
				return strtotime($e);
			}, $dates);
			rsort($dates);
			$lastPageMod = array_shift($dates);
		}
		$webLastMod = strtotime($context['web']['modification_date']);
		$webLastMod = max($webLastMod,$lastPageMod);
		$path = './sitemap.xml';
		$update_sitemap = false;

		if (!file_exists($path)) {
			$update_sitemap=true;
		}
		elseif (!filesize($path)) {
			$update_sitemap=true;}
		else {
			$lastMod = filemtime($path);
			if($webLastMod<0 ||$lastMod<0) { $update_sitemap=true;}
			else {
				if($lastMod<$webLastMod) $update_sitemap=true;
			}
		}

		if($update_sitemap) {
			$web = $context['web']['values'];
			$domainURL =$web['domaine-principal-pour-information']['value'];
			if(!$domainURL) {
				$web = $this->getWeb()['values'];
				$force_www = isset($web['force_www']) && $web['force_www'];
				$force_https = isset($web['force_https']) && $web['force_https'];
				$redirect = $_SERVER["SERVER_NAME"];

				if ($force_www && substr($_SERVER["SERVER_NAME"], 0, 4) !== "www.") {
					$redirect = "www." . $redirect;

				}
				if ($force_https ) {
					$redirect = "https://" . $redirect;
				}	 else {
					$redirect = "http://".$redirect;
				}
				$domainURL=$redirect."/";
			}
			$domainURL=rtrim($domainURL,'/').'/';
			$xml = new \XMLWriter();
			$xml->openMemory();

			$xml->startDocument("1.0",'UTF-8');
			$xml->startElement("urlset");
			$xml->startAttribute('xmlns',"http://www.sitemaps.org/schemas/sitemap/0.9");
			$xml->writeAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');
			$xml->endAttribute();


			$pages = array_filter($context['menu'],function($item) { return $item['values']['status']['value']=='online';});
			if($pages) {
				foreach($pages as $page) {
					$values = $page['values'];
					$ts = strtotime($page['modification_date']);

					$xml->startElement('url');
					$xml->startElement('loc');
					$xml->text($domainURL.$values['url']['value']);
					$xml->endElement();
					$xml->startElement('lastmod');
					$xml->text(gmdate("Y-m-d", $ts) .'T'. gmdate("H:m:s", $ts) . '+01:00');
					$xml->endElement();
					$xml->endElement();
				}
			}
			$xml->endElement();
			$sitemap = $xml->outputMemory();
			file_put_contents($path, $sitemap);
		}
	}
    /**
     * Load and return current page
     *
     * @return mixed|string
     */
    public function render()
    {
        try {

			$this->redirectHttpsWww();
			$this->handleXHR();


            $this->compilScss();

            // Detect type of page
            $pageType = $this->getRequestPageType();

            $context = $this->getTwigContext();
			$this->refreshSitemap($context);
            if (!$context["web"]) {
                return $this->renderError(404);
            }
            if (!$context["current_page"]) {
                return $this->renderError(404);
                // TODO si la page n'est pas trouvée, regarder en + si elle existe via l'API
            }

            $this->handlePostDatas($context["current_page"]);

            if ($this->canView($context["current_page"])) {
                return $this->getTwigEnvironment()->render($pageType . '.html.twig', $context);
            } else {
                return $this->getTwigEnvironment()->render($pageType . '_login.html.twig', $context);
            }
        } catch (\Exception $e) {
            return $this->renderError($e->getCode(), $e->getMessage());
        }
    }

    /**
     *
     */
    protected function redirectHttpsWww()
    {
		$web = $this->getWeb()['values'];
		$force_www = isset($web['force_www']) && $web['force_www'];
		$force_https = isset($web['force_https']) && $web['force_https'];
		$redirect = $_SERVER["SERVER_NAME"];

		if ($force_www && substr($_SERVER["SERVER_NAME"], 0, 4) !== "www.") {
			$redirect = "www." . $redirect;

		}
		if ($force_https && !self::_isHttps() && $_SERVER["REMOTE_ADDR"] !== "127.0.0.1") {
			$redirect = "https://" . $redirect;
		}

		if ($redirect !== $_SERVER["SERVER_NAME"]) {

			if (substr($redirect, 0, 8) !== "https://" && substr($redirect, 0, 8) !== "http://") {
				$redirect = "http://" . $redirect;
			}

            if(isset($this->request_uri) && $this->request_uri)$redirect .= "/" . $this->request_uri;

			header("Location: " . $redirect, true, 301);
			exit;
		}
    }

    /**
     * @return bool
     */
    protected static function _isHttps()
    {
        return isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }

    /**
     * @param int $code
     * @return mixed
     */
    protected function renderError($code = 404, $message = "Page not found")
    {
        http_response_code($code);
        return $this->getTwigEnvironment()->render('error.html.twig', ["code" => $code, "message" => $message]);
    }

    /**
     * Handle all POST
     *
     */
    protected function handlePostDatas($page)
    {
        $datas = $_POST;
        if (isset($datas["password"])) {
            // Verify page password
            $isValid = $this->api->isValid($this->getRequestPageModuleId(), $page["unique"], ["password" => $datas["password"]]);
            if ($isValid["isValid"]["password"]) {
                // Set to session
                $_SESSION["allowed_pages"][$page["unique"]] = time();
                $this->reload();
            } else {
                // Redirect to same page with flash
                $this->reload(["errors" => ["password" => "no_allowed"]]);
            }
        }
		if(isset($datas['form']) && isset($datas['formId'])) {
			$this->api->addElement($datas['form'], $datas);
			$this->reload();
		}
    }

    /**
     * @param array $gets
     * @throws \Exception
     */
    protected function reload(array $gets = [])
    {
        $fullUri = "/" . $this->getRequestUri();
        if ($gets) {
            $fullUri .= "?" . http_build_query($gets);
        }
        header("Location: " . $fullUri);
    }

    /**
     * @param $page
     * @return bool
     */
    protected function canView($page): bool
    {
        $return = false;

        if (isset($page["password"]) && $page["password"]) {
            if (isset($_SESSION["allowed_pages"][$page["unique"]])) {
                // check secured time
                $sessionTime = $_SESSION["allowed_pages"][$page["unique"]];
                $ttl = $this->config->secured_pages_ttl;
                $limit = $sessionTime+$ttl;
                $now = time();
                if ($now <= $limit) {
                    $return = true;
                }
            }
        } else {
            $return = true;
        }

        return $return;
    }

    /**
     * @param bool $fromApi
     * @return array|null
     * @throws \Exception
     */
    private function getWeb(bool $fromApi = false): ?array
    {
        if ($fromApi) {
            $webs = $this->api->getModuleElements($this->config->web_module_id)["results"];
            foreach ($webs as $web) {
                if ($web["unique"] === $this->config->web_unique) {
                    $this->web = $web;
                    break;
                }
            }
        }
        return $this->web;
    }

	private function getFooterMenus() {
		$all = $this->api->getModuleElements($this->getPageModuleId())["results"];
		$pages = $this->filterElements($all, $this->getWeb()["values"]["pages"]);

		$return = null;
		if($pages && count($pages)) {
			$pages = array_filter($pages,function($page) {
				return is_array($page["values"]["afficher-aussi-dans"]) && count($page["values"]["afficher-aussi-dans"]) && $page["values"]["status"]["value"]=="online";
			});

			if($pages && count($pages)) {
				$return = [];
				foreach($pages as $page) {

					$children = $this->getChildrens($page,"footer");
					$page['show_in']= null;

					if(is_array($page["values"]["afficher-aussi-dans"]) && count($page["values"]["afficher-aussi-dans"])) {
						$page['show_in'] = array_column($page["values"]["afficher-aussi-dans"], 'value');
					}
					$page['visible']= $page["values"]["in_menu"]["value"];
					$page['submenu'] = $children ? $children : null;
					$page['haveSubmenu'] = $children && count($children) ? true:false;
					$return[]=$page;
				}
			}
		}
		return $return;
	}
    /**
     * @return array|mixed
     * @throws \Exception
     */
    private function getMenu()
    {
        $all = $this->api->getModuleElements($this->getPageModuleId())["results"];
        $pages = $this->filterElements($all, $this->getWeb()["values"]["pages"]);

		$return = null;
		if($pages && count($pages)) {


			$pages = array_filter($pages,function($page) {
//				return $page["values"]["in_menu"]["value"] && !$page["values"]["parent-page"]["value"];
				return  $page["values"]["status"]["value"]=="online" && !$page["values"]["parent-page"]["value"];
			});

			if($pages && count($pages)) {
				$return = [];
				foreach($pages as $page) {

					$children = $this->getChildrens($page);
					$page['show_in']= null;

					if(is_array($page["values"]["afficher-aussi-dans"]) && count($page["values"]["afficher-aussi-dans"])) {
						$page['show_in'] = array_column($page["values"]["afficher-aussi-dans"], 'value');
					}
					$page['visible']= $page["values"]["in_menu"]["value"];
					$page['submenu'] = $children ? $children : null;
					$page['haveSubmenu'] = $children && count($children) ? true:false;
					$return[]=$page;
				}
			}

		}


		return $return;
    }

	private function getChildrens($current_page,$whereLoc="header") {


		$all = $this->api->getModuleElements($this->getPageModuleId())["results"];
		$pages = $this->filterElements($all, $this->getWeb()["values"]["pages"]);
		$return = null;
		if($pages && count($pages)) {
			$pages = array_filter($pages, function ($page) use ($current_page,$whereLoc) {
				if($whereLoc=="footer") {
					return  $page["values"]["status"]["value"]=="online" && is_array($page["values"]["afficher-aussi-dans"]) && count($page["values"]["afficher-aussi-dans"]) && $page["values"]["parent-page"]["value"] == $current_page["unique"];
				}
				else return  $page["values"]["status"]["value"]=="online" && $page["values"]["in_menu"]["value"] && $page["values"]["parent-page"]["value"] == $current_page["unique"];
			});
			if ($pages && count($pages)) {
        $return = [];
        foreach ($pages as $page) {
					$children = $this->getChildrens($page);
			$page['show_in']= [];
			if(is_array($page["values"]["afficher-aussi-dans"]) && count($page["values"]["afficher-aussi-dans"])) {
				$page['show_in'] = array_column($page["values"]["afficher-aussi-dans"], 'value');
			}
			$page['visible']= $page["values"]["in_menu"]["value"];
					$page['submenu'] = $children ? $children : null;
					$page['haveSubmenu'] = $children && count($children) ? true:false;
                $return[] = $page;
            }
        }
		}
        return $return;
    }

    /**
     * @param array $pages
     * @return array|mixed
     * @throws \Exception
     */
    private function getCurrentPage()
    {
        $return = null;

        if ($this->getRequestPageType() === "pages") {
            $all = $this->api->getModuleElements($this->getPageModuleId())["results"];
            $all = $this->filterElements($all, $this->getWeb()["values"]["pages"]);

            foreach ($all as $item) {
                if ($item["values"]["url"]["value"] === $this->getRequestUri()) {
                    $return = $item;
                    break;
                }
            }

            if ($return) {
                $return["sections"] = $this->getCurrentPageSections($return);
            }
        } else {
            $all = $this->api->getModuleElements($this->getRequestPageModuleId())["results"];
            $all = $this->filterElements($all, false);

            foreach ($all as $item) {
                if ($this->getRequestPageType() . "/" . $item["values"]["url"]["value"] === $this->getRequestUri()) {
                    $return = $item;
                    break;
                }
            }
        }

		$module = $this->api->getModuleInfos($this->getPageModuleId());
		$config = $this->api->getModuleElementField($this->getPageModuleId(), 'sections');

		$return['editor'] = [
			"slug" => $module['slug'],
			'id' => $module['id'],
			"section" => ($config ? $config['id']:0)
		];
        return $return;
    }

    /**
     * @param array $currentPage
     * @return array
     * @throws \Exception
     */
    private function getCurrentPageSections(array $currentPage)
    {
        $all = $this->api->getModuleElements($this->getSectionModuleId())["results"];
        $return = $this->filterElements($all, $currentPage["values"]["sections"]);

		$module = $this->api->getModuleInfos($this->getSectionModuleId());
		$config = $this->api->getModuleElementField($this->getSectionModuleId(), 'blocs');

        // Add Blocs to each items
        foreach ($return as $key => $item) {
			$return[$key]['module']=['slug' =>$module['slug'],'id' =>$module['id'],'add' => $config?$config['id']:null];
            $return[$key]["blocs"] = $this->getCurrentPageSectionBlocs($item);
        }

        return $return;
    }

    /**
     * @param array $section
     * @return array
     * @throws \Exception
     */
    private function getCurrentPageSectionBlocs(array $section)
    {
        $all = $this->api->getModuleElements($this->getBlocModuleId())["results"];
        $return = $this->filterElements($all, $section["values"]["blocs"]);

        $module = $this->api->getModuleInfos($this->getBlocModuleId());
		$moduleSection = $this->api->getModuleInfos($this->getSectionModuleId());
		$config = $this->api->getModuleElementField($this->getSectionModuleId(), 'blocs');

		foreach ($return as $key => $bloc) {
			if($bloc['values']['formId']['value']) {
				$return[$key]['values']['type']['value']='form';
				$vals = explode('|',$bloc['values']['formId']['value']);
				if($vals && count($vals)==2) {
					$miniFormId=$vals[1];
					$formModule = $this->api->getModuleInfos($vals[0]);
					$formConfig = $this->api->getModuleElementformConfigFields($vals[0],$vals[1]);
					if(isset($formConfig['components'])) {
						// Rewrite des datas reçues

						$component = array_filter($formConfig['components'],function($e) { return $e['type']=='Panel';});
						if($component) {
							$component = array_shift($component);

							$module_forms = array_filter($formModule['module_forms'],function($e) use($miniFormId) {
								return $e['id']==$miniFormId;
							});
							if($module_forms) {
								$module_forms = array_shift($module_forms);
								unset($module_forms['config']);
							}

							$formDatas =
							array_merge($module_forms,
								[
									'submitButtonLabel' => $formConfig['submitButtonLabel'],
									'moduleId' => $vals[0],'miniFormId' => $vals[1],'action' => $formConfig['action'],'method' => $formConfig['method']]);

							$fields = array_filter($component['components'],function($e) { return $e['type']=='Tabs';});

							if($fields && is_array($fields)) {

								$fields = array_shift($fields);
								$fields = array_filter($fields['components'],function($e) { return $e['type']=='Tab';});

								$formDatas['slug'] =isset($fields[0]['slug'])?$fields[0]['slug']:null;
								$formDatas['fields']=$fields[0]['components'];
								$return[$key]['form']=$formDatas;

							}
						}
					}
				}

			}

            if ($bloc["values"]["load_from"]["value"]) {
				$moduleSrcID = $bloc["values"]["load_from"]["value"];
                $bloc["load_from"] = $this->api->getModuleInfos($bloc["values"]["load_from"]["value"]);
                $confToLoad = [
                    'module' => $bloc["load_from"]["id"],
                    'filter' => []
                ];
                if ($bloc["values"]["load_order"]["value"]) {
                    $confToLoad["order"] = [
                        [$bloc["values"]["load_order"]["value"], $bloc["values"]["load_order_dir"]["value"] ?: "asc"]
                    ];
                }
                if ($bloc["values"]["load_limit"]["value"]) {
                    $confToLoad["limit"] = [0, $bloc["values"]["load_limit"]["value"]];
                }
                $datasToLoad = $this->api->getItems($confToLoad);
                $datasToLoad = array_map(function ($item) use ($bloc) {
                    $item = array_merge($bloc, $item);
                    $item["values"] = array_merge($bloc["values"], $item["values"]);
                    return $item;
                }, $datasToLoad);

                $datasToLoad = $this->filterElements($datasToLoad, false);

                $tmp = array_slice($return, 0, $key);
                $tmp = array_merge($tmp, $datasToLoad);
                $tmp = array_merge($tmp, array_slice($return, $key+1));

                $return = $tmp;
				$module = $this->api->getModuleInfos($moduleSrcID);
				$config = $this->api->getModuleElementField($this->getSectionModuleId(), 'blocs');
				foreach($return as $k => $v) {
					$return[$k]['section']=['slug' =>$moduleSection['slug'],'id' =>$this->getSectionModuleId(),'unique' => $section['unique'],'add' => $config?$config['id']:''];
					$return[$k]['module']=['slug' =>$module['slug'],'id' =>$module['id']];
				}
            } else {
				$return[$key]['section']=['slug' =>$moduleSection['slug'],'id' =>$this->getSectionModuleId(),'unique' => $section['unique'],'add' => $config?$config['id']:''];
				$return[$key]['module']=['slug' =>$module['slug'],'id' =>$module['id']];
			}
        }
        return $return;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getTwigContext(): array
    {
        $web = $this->getWeb();
        $menu = $this->getMenu();
		$footerMenu = $this->getFooterMenus();
        $currentPage = $this->getCurrentPage();

        $moduleId = $this->getRequestPageModuleId();
		$module = $this->api->getModuleInfos($moduleId);
        return [
            "web" => $web,
            "menu" => $menu,
			"footer_menu" => $footerMenu,
			"moduleId" => $moduleId,
			"module" => $module,
			"config" => $this->config,
            "current_page" => $currentPage,
            "request_uri" => $this->getRequestUri()
        ];
    }

    /**
     * @return array
     */
    private function getTemplatePaths()
    {
        $paths = [];
        $overrideDir = "./template";
        if (is_dir($overrideDir)) {
            $paths[] = $overrideDir;
        }
        $paths[] = $this->config->class_root_dir . 'templates/' . $this->config->template;
        return $paths;
    }

    /**
     * @return Environment
     */
    private function getTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader($this->getTemplatePaths());
        $return = new Environment($loader,[ 'debug' => true]);
		$return->addExtension(new \Twig\Extension\DebugExtension());
        $return->addExtension(new TwigExtension($this, $loader));
        return $return;
    }

    /**
     * @param array $elements
     * @param array|string $uniques
     * @return array
     */
    private function filterElements(array $elements, $uniques): array
    {
        if ($uniques !== false && !is_array($uniques)) {
            $uniques = [$uniques];
        }

        if (is_array($uniques) && isset($uniques[0]["value"])) {
            // Is array of object. Trasnform it to array of uniques
            $tmp = [];
            foreach ($uniques as $unique) {
                $tmp[] = $unique["value"];
            }
            $uniques = $tmp;
        }

        $return = [];
        foreach ($elements as $element) {
            if ($uniques !== false && !in_array($element["unique"], $uniques)) {
                continue;
            }
            if (isset($element["values"]["status"]["value"]) && $element["values"]["status"]["value"] != "online") {
                continue;
            }
            if (isset($element["values"]["web"])) {
                $tmp = [];

                foreach ($element["values"]["web"] as $value) {
                    $tmp[] = $value["value"];
                }

                if (!in_array($this->getWeb()["unique"], $tmp)) {
                    continue;
                }
            }
            if (isset($element["values"]["publication_date"]["value"])) {
                $publication = strtotime($element["values"]["publication_date"]["value"]);
                if ($publication > time()) {
                    continue;
                }
            }
            $return[] = $element;
        }

        if ($uniques !== false) {
            $orders = array_flip($uniques);
            usort($return, function ($a, $b) use ($orders) {
                return $orders[$a["unique"]] > $orders[$b["unique"]] ? 1 : -1;
            });
        }

        return $return;
    }

    /**
     *
     */
    protected function compilScss()
    {
        $scssDir = "./template/assets/src/scss/";
        $cssDir = "./template/assets/dist/css/";
        $cssFile = "theme.bundle.css";

        $do = false;
        if ($this->config->use_scssphp && is_dir($scssDir)) {
            $scandir = scandir($scssDir);
            unset($scandir[0]);
            unset($scandir[1]);

            if ($scandir) {
                $filesDates = [];
                foreach ($scandir as $scssFile) {
                    $filesDates[] = filemtime($scssDir . $scssFile);
                }
                rsort($filesDates);

                if (!file_exists($cssDir . $cssFile) || filemtime($cssDir . $cssFile) < $filesDates[0]) {
                    $do = true;
                }
            }
        }

        if ($do) {
            @mkdir($cssDir, 0755, true);

            $importPaths = [];
            foreach ($this->getTemplatePaths() as $templatePath) {
                $importPaths[] = $templatePath . "/assets/src/scss/";
            }

            $compiler = new Compiler();
            $compiler->setOutputStyle(OutputStyle::COMPRESSED);
            $compiler->addImportPath(function ($path) use ($importPaths) {
                if (Compiler::isCssImport($path)) {
                    return null;
                }

                $pathExplode = explode("/", $path);
                $last = array_pop($pathExplode);
                $path = trim(implode("/", $pathExplode) . "/_" . $last . ".scss", "/");
                $pathAlt = trim(implode("/", $pathExplode) . "/" . $last . ".scss", "/");

                if (substr($path, 0, 1) === "~") {
                    // Include from node_modules
                    $path = substr($path, 1);
                    $pathAlt = substr($pathAlt, 1);
                }

                $inc = null;
                // Include from template
                foreach ($importPaths as $importPath) {
                    if (file_exists($importPath . $path)) {
                        $inc = $importPath . $path;
                        break;
                    } elseif (file_exists($importPath . $pathAlt)) {
                        $inc = $importPath . $pathAlt;
                        break;
                    }
                }
                if (!$inc) {
                    // Include from node_modules
                    if (file_exists($this->config->class_root_dir . 'templates/' . $this->config->template . "/assets/node_modules/" . $path)) {
                        $inc = $this->config->class_root_dir . 'templates/' . $this->config->template . "/assets/node_modules/" . $path;
                    } elseif (file_exists($this->config->class_root_dir . 'templates/' . $this->config->template . "/assets/node_modules/" . $pathAlt)) {
                        $inc = $this->config->class_root_dir . 'templates/' . $this->config->template . "/assets/node_modules/" . $pathAlt;
                    }
                }

                return $inc;
            });

            $themeScssFile = $this->config->class_root_dir . "templates/" . $this->config->template . "/assets/src/scss/theme.scss";
            $scssToCompil = file_get_contents($themeScssFile);

            $css = $compiler->compileString($scssToCompil)->getCss();

            file_put_contents($cssDir . $cssFile, $css);

            // Copy font
        }
    }
}