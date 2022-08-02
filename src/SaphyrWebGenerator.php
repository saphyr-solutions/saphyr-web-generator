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
    public $config;

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
    public function getRequestUri(): string
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

	/**
	 * Build sitemap.xml if new content added
	 * @param $context
	 * @return void
	 * @throws \Exception
	 */
    protected function refreshSitemap($context)
    {
        $path = './sitemap.xml';
        $updateSitemap = false;

        // Trouver les modules qui sont dispo sur le site
        $modulesIdsWithPages = [$this->getPageModuleId()];
        $allBlocs = $this->api->getModuleElements($this->getBlocModuleId())["results"];
        foreach ($allBlocs as $bloc) {
            $loadFromId = (int)$bloc["values"]["load_from"]["value"];
            if ($loadFromId && !in_array($loadFromId, $modulesIdsWithPages)) {
                $modulesIdsWithPages[] = $loadFromId;
            }
        }

        if (!file_exists($path)) {
            $updateSitemap = true;
        } else {
            // Trouver les date de modification des modules dispo sur le site
            $modificationsDates = [];
            foreach ($modulesIdsWithPages as $moduleIdWithPage) {
                $moduleInfos = $this->api->getModuleInfos($moduleIdWithPage);
                $modificationsDates[] = strtotime($moduleInfos['modification_date']);
            }
            $modificationsDates[] = strtotime($context["web"]["modification_date"]);
            rsort($modificationsDates);

            $sitemapModificationDate = filemtime($path);
            if ($modificationsDates[0] > $sitemapModificationDate) {
                $updateSitemap = true;
            }
        }

        if ($updateSitemap) {
            if ($context["web"]["values"]["domain_name"]["value"]) {
                $domainURL = $context["web"]["values"]["domain_name"]["value"];
            } else {
                $domainURL = (self::_isHttps() ? "https" : "http") . "://" . $_SERVER["SERVER_NAME"];
            }

            $xml = new \XMLWriter();
            $xml->openMemory();
            $xml->startDocument("1.0", 'UTF-8');
            $xml->startElement("urlset");
            $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $xml->endAttribute();

            foreach ($modulesIdsWithPages as $moduleIdWithPage) {
                $allPages = $this->api->getModuleElements($moduleIdWithPage)["results"];

                if ($moduleIdWithPage === $this->getPageModuleId()) {
                    $allPages = $this->filterElements($allPages, $context["web"]["values"]["pages"]);
                } else {
                    $allPages = $this->filterElements($allPages, false);
                }
                foreach ($allPages as $page) {
                    $values = $page['values'];
                    $url = self::getHref($values['url']['value']);
                    if (substr($url, 0, 1) !== "/") {
                        continue;
                    }
                    $url = explode("#", $url, 2)[0];

                    if ($moduleIdWithPage === $this->getPageModuleId()) {
                        $fullUrl = $domainURL . $url;
                    } else {
                        $moduleSlug = $this->api->getModuleInfos($moduleIdWithPage)["slug"];
                        $fullUrl = $domainURL . "/" . $moduleSlug . $url;
                    }

                    $xml->startElement('url');
                    $xml->startElement('loc');
                    $xml->text($fullUrl);
                    $xml->endElement();
                    $xml->startElement('lastmod');
                    $xml->text((new \DateTime($page['modification_date']))->format("c"));
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

            $this->compilScss();

            // Detect type of page
            $pageType = $this->getRequestPageType();

            // CHeck logout GET
            $this->handleLogout();

            $context = $this->getTwigContext();
            if (!$context["web"]) {
                return $this->renderError(404);
            }

            $this->refreshSitemap($context);

            if (!$context["current_page"]) {
                return $this->renderError(404);
                // TODO si la page n'est pas trouvée, regarder en + si elle existe via l'API
            }
            $this->handlePostDatas();
            return $this->getTwigEnvironment()->render($pageType . '.html.twig', $context);
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
		if ($force_https && !self::_isHttps() && !Api::_isLocalhost()) {
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
     * @param $url
     * @return mixed|string
     */
    public static function getHref($url) {
        if (substr($url, 0, 8) === "https://") {
            $return = $url;
        } elseif (substr($url, 0, 7) === "http://") {
            $return = $url;
        } elseif (substr($url, 0, 4) === "tel:") {
            $return = $url;
        } elseif (substr($url, 0, 7) === "mailto:") {
            $return = $url;
        } elseif (substr($url, 0, 1) === "#") {
            $return = $url;
        } elseif (substr($url, 0, 1) === "/") {
            $return = $url;
        } else {
            $return = "/" . $url;
        }

        return $return;
    }

    /**
     * @param int $code
     * @return mixed
     */
    protected function renderError($code = 404, $message = "Page not found")
    {
        http_response_code($code);

        if (isset($_POST["action"])) {
            die(json_encode(["error" => [
                "code" => $code,
                "message" => $message
            ]]));
        } else {
            return $this->getTwigEnvironment()->render('error.html.twig', ["code" => $code, "message" => $message]);
        }
    }

    /**
     * Handle logout
     *
     */
    protected function handleLogout()
    {
        if (isset($_GET["logout"]) && $_GET["logout"]) {
            unset($_SESSION["allowed_parts"]);
            $this->reload();
        }
    }

    /**
     * Handle all POST
     *
     */
    protected function handlePostDatas()
    {
        $datas = $_POST;

        if (isset($datas["action"])) {
            $method = $datas["method"];
            $action = $datas["action"];
            $signature = $datas["signature"];

            unset($datas["method"]);
            unset($datas["action"]);
            unset($datas["signature"]);

            // check signature
            $signatureParts = [$method, $action];
            sort($signatureParts);

            $realSignature = hash_hmac("sha256", implode("+", $signatureParts), $this->api->getPrivateKey());
            if ($realSignature !== $signature) {
                throw new \Exception("Are you a robot ?", 500);
            } else {
                // Handle form
                $call = $this->api->callEndpoint($action, $method, $datas);
                if ($call) {
                    die(json_encode([
                        "success" => true
                    ]));
                } else {
                    throw new \Exception("An error occured", 500);
                }
            }
        } else if (isset($datas["part_unique"]) && isset($datas["part_module_id"])) {
            // Verify part access
            $partUnique = $datas["part_unique"];
            $partModuleId = $datas["part_module_id"];
            unset($datas["part_unique"]);
            unset($datas["part_module_id"]);

            $redirectGets = ["errors" => ["password" => "no_allowed"]];
            if (isset($datas["login"]) && $datas["login"]) {
                $redirectGets["login"] = $datas["login"];
            }
            $session = ["time" => time()];
            $isValid = false;

            $parts = $this->api->getModuleElements($partModuleId)["results"];
            $parts = $this->filterElements($parts, $partUnique);

            if ($parts[0]) {
                $part = $parts[0];
                if (
                    isset($part["values"]["account_field_login"])
                    && $part["values"]["account_field_login"]["value"]
                    && isset($part["values"]["account_field_password"])
                    && $part["values"]["account_field_password"]["value"]
                ) {
                    // Check login from fields
                    if ($datas["password"] && $datas["login"]) {
                        $linkedModuleId = (int)explode("|", $part["values"]["account_field_login"]["value"], 2)[0];
                        $linkedLoginField = explode("|", $part["values"]["account_field_login"]["value"], 2)[1];
                        $linkedPasswordField = explode("|", $part["values"]["account_field_password"]["value"], 2)[1];

                        $linkedElementsFound = [];
                        $linkedElementsOnLogin = $this->api->getItems([
                            "module" => $linkedModuleId,
                            "filter" => [
                                [
                                    "reference" => $linkedLoginField,
                                    "condition" => "=",
                                    "value" => $datas["login"]
                                ]
                            ]
                        ]);
                        foreach ($linkedElementsOnLogin as $linkedElement) {
                            // Check password
                            $passwordCheck = $this->api->isValid($linkedModuleId, $linkedElement["unique"], [$linkedPasswordField => $datas["password"]]);
                            if ($passwordCheck["isValid"]["password"]) {
                                $linkedElementsFound[] = $linkedElement;
                            }
                        }

                        if ($linkedElementsFound) {
                            $isValid = true;
                            $session["accounts_uniques"] = [];
                            foreach ($linkedElementsFound as $item) {
                                $session["accounts_uniques"][] = $item["unique"];
                            }
                        }
                    }
                } else if (isset($part["values"]["password"]) && $part["values"]["password"]["value"]) {
                    if ($datas["password"]) {
                        // Check password
                        $passwordCheck = $this->api->isValid($partModuleId, $partUnique, ["password" => $datas["password"]]);
                        if ($passwordCheck["isValid"]["password"]) {
                            $isValid = true;
                        }
                    }
                }
            }

            if ($isValid) {
                // Set to session
                $_SESSION["allowed_parts"][$partUnique] = $session;
                $this->reload();
            } else {
                // Redirect to same page with flash
                $this->reload($redirectGets);
            }
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
        exit;
    }

    /**
     * @param $part
     * @return bool
     */
    protected function canView($part): bool
    {
        $isSecured = false;

        if (isset($part["values"]["password"]) && $part["values"]["password"]["value"]) {
            $isSecured = true;
        } elseif (
            isset($part["values"]["account_field_login"])
            && $part["values"]["account_field_login"]["value"]
            && isset($part["values"]["account_field_password"])
            && $part["values"]["account_field_password"]["value"]
        ) {
            $isSecured = true;
        }

        $return = true;
        if ($isSecured) {
            $return = false;
            if (isset($_SESSION["allowed_parts"][$part["unique"]])) {
                // check secured time
                $sessionTime = (int)$_SESSION["allowed_parts"][$part["unique"]]["time"];
                $ttl = $this->config->secured_parts_ttl;
                $limit = $sessionTime+$ttl;
                $now = time();
                if ($now <= $limit) {
                    $return = true;
                    $_SESSION["allowed_parts"][$part["unique"]]["time"] = $now;
                }
            }
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

    /**
     * @return array
     * @throws \Exception
     */
    private function getMenus()
    {
        $all = $this->api->getModuleElements($this->getPageModuleId())["results"];
        $allPages = $this->filterElements($all, $this->getWeb()["values"]["pages"]);
        $allPagesArbo = [];
        $return = [];

        // Créer une arborescence de base avec toutes les pages, peu importe l'emplacement
        $firstLevelPages = array_filter($allPages,function($page) {
            return !$page["values"]["parent_page"]["value"];
        });
        foreach($firstLevelPages as $page) {
            $page["childrens"] = $this->getPageChildrens($allPages, $page);
            $allPagesArbo[] = $page;
        }

        // Trouver les emplacement disponible
        $allShowIn = ["nav"];
        foreach ($allPages as $page) {
            if (is_array($page["values"]["show_in"])) {
                foreach ($page["values"]["show_in"] as $showIn) {
                    $allShowIn[] = $showIn["value"];
                }
            }
        }
        $allShowIn = array_unique($allShowIn);

        // Ajouter dans l'arborscence de base dans les emplacements
        // Et trier les pages qui sont à afficher seulement dans cet emplacement
        foreach ($allShowIn as $showIn) {
            $finalArbo = [];
            $this->filterArboMenuPages($finalArbo, $allPagesArbo, $showIn);
            $return[$showIn] = $finalArbo;
        }

		return $return;
    }

    /**
     * @param array $finalArbo
     * @param array $allPagesArbo
     * @param string $showIn
     * @return void
     */
    private function filterArboMenuPages(&$finalArbo, $allPagesArbo, $showIn)
    {
        foreach ($allPagesArbo as $page) {
            $childrens = $page["childrens"];
            $page["childrens"] = [];

            $isValid = false;
            if ($showIn === "nav") {
                if ($page["values"]["in_menu"]["value"]) {
                    $isValid = true;
                }
            } else {
                $allPageShowIn = [];
                if (is_array($page["values"]["show_in"])) {
                    foreach ($page["values"]["show_in"] as $pageShowIn) {
                        $allPageShowIn[] = $pageShowIn["value"];
                    }
                }
                if (in_array($showIn, $allPageShowIn)) {
                    $isValid = true;
                }
            }

            if ($isValid) {
                $this->filterArboMenuPages($page["childrens"], $childrens, $showIn);
                $finalArbo[] = $page;
            } else {
                $this->filterArboMenuPages($finalArbo, $childrens, $showIn);
            }
        }
    }

    /**
     * @param $allPages
     * @param $page
     * @return array
     */
	private function getPageChildrens($allPages, $currentPage)
    {
        $return = [];

        foreach ($allPages as $page) {
            if ($page["values"]["parent_page"]["value"] === $currentPage["unique"]) {
                $page["childrens"] = $this->getPageChildrens($allPages, $page);
                $return[] = $page;
            }
        }

        return $return;
    }

    /**
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

        if ($return) {
            $return["can_view"] = $this->canView($return);

            $module = $this->api->getModuleInfos($this->getPageModuleId());
            $config = $this->api->getModuleElementField($this->getPageModuleId(), 'sections');
            $return['editor'] = [
                "slug" => $module['slug'],
                'id' => $module['id'],
                "section" => ($config ? $config['id']:0)
            ];
        }

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
            $return[$key]["can_view"] = $this->canView($item);
            $return[$key]['module'] = [
                'slug' => $module['slug'],
                'id' => $module['id'],
                'add' => $config ? $config['id'] : null
            ];
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
            // Handle form
            if ($bloc['values']['form_id']['value']) {
                $return[$key]['values']['type']['value'] = 'form';
                $vals = explode('|', $bloc['values']['form_id']['value']);
                if ($vals && count($vals) == 2) {
                    $miniFormId = (int)$vals[1];
                    $miniFormModuleId = (int)$vals[0];

                    $formConfig = $this->api->getModuleElementFormConfig($miniFormModuleId, $miniFormId);

                    if (isset($formConfig['components'])) {
                        // Remove Panel, Tabs and Tab (Keep field only)
                        $fields = [];
                        foreach ($formConfig['components'] as $panel) {
                            foreach ($panel['components'] as $tabs) {
                                foreach ($tabs['components'] as $tab) {
                                    foreach ($tab['components'] as $field) {
                                        $fields[] = $field;
                                    }
                                }
                            }
                        }
                        if ($fields) {
                            // Write signature
                            $formConfig['fields'] = $fields;
                            $formConfig['signature'] = $this->getSignatureFromFormConfig($formConfig);
                            $return[$key]['form'] = $formConfig;
                        }
                    }
                }
            }

            // Handle accounts
            if ($bloc["values"]["account_field_login"]["value"] && $bloc["values"]["account_field_password"]["value"]) {
                $accountsUniques = [];
                $accounts = [];

                // Find accounts logged
                if (isset($_SESSION["allowed_parts"][$bloc["unique"]]["accounts_uniques"]) && is_array($_SESSION["allowed_parts"][$bloc["unique"]]["accounts_uniques"])) {
                    $accountsUniques = $_SESSION["allowed_parts"][$bloc["unique"]]["accounts_uniques"];
                }

                if ($accountsUniques) {
                    $accountModuleId = (int)explode("|", $bloc["values"]["account_field_login"]["value"], 2)[0];
                    $allAccounts = $this->api->getModuleElements($accountModuleId)["results"];
                    $accounts = $this->filterElements($allAccounts, $accountsUniques);
                }

                $return[$key]['accounts'] = $accounts;
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

        foreach ($return as $key => $item) {
            $return[$key]["can_view"] = $this->canView($item);
        }

        return $return;
    }

    /**
     * @param $formConfig
     * @return string
     */
    private function getSignatureFromFormConfig($formConfig)
    {
        $signatureParts = [$formConfig["action"], $formConfig["method"]];
        sort($signatureParts);

        return hash_hmac("sha256", implode("+", $signatureParts), $this->api->getPrivateKey());
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getTwigContext(): array
    {
        $web = $this->getWeb();
        $menus = $this->getMenus();
        $currentPage = $this->getCurrentPage();

        $moduleId = $this->getRequestPageModuleId();
		$module = $this->api->getModuleInfos($moduleId);

        return [
            "web" => $web,
            "menus" => $menus,
			"moduleId" => $moduleId,
			"module" => $module,
			"config" => $this->config,
            "current_page" => $currentPage
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
        $return = new Environment($loader);
        $return->addExtension(new TwigExtension($this, $loader));
        return $return;
    }

    /**
     * @param array $elements
     * @param array|string $uniques
     * @return array
     */
    public function filterElements(array $elements, $uniques): array
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