<?php
namespace Grav\Plugin;

use Grav\Common\Page\Markdown\Excerpts;
use Grav\Common\Plugin;

class TinyMCEEditorPlugin extends Plugin {
	public static function getSubscribedEvents() {
		return ["onAdminSave" => ["onAdminSave", 0], "onPluginsInitialized" => ["onPluginsInitialized", 0], "onTwigSiteVariables" => ["onTwigSiteVariables", 0], "onPageContentProcessed" => ["onPageContentProcessed", 0]];
	}
	public function onAdminSave($event) {
		$page = $event["object"];
		if($page instanceof Page && $page->folder() != $page->getOriginal()->folder() && $page->rawMarkdown() != "") {
			$newdir = explode(".", $page->folder());
			$newdir = $this->grav["uri"]->rootUrl() . $page->parent()->route() . "/" . end($newdir) . "/";
			$dom = new \DOMDocument("1.0", "UTF-8");
			@$dom->loadHTML(mb_convert_encoding($page->rawMarkdown(), "HTML-ENTITIES", "UTF-8"), LIBXML_PARSEHUGE);
			foreach($page->getOriginal()->media()->all() as $key => $value) {
				foreach($dom->getElementsByTagName("img") as $tag) {
					$query = parse_url($tag->getAttribute("src"), PHP_URL_QUERY);
					if($query != NULL) {
						$query = "?" . $query;
					}
					if($tag->getAttribute("src") == $this->grav["uri"]->rootUrl() . $page->getOriginal()->route() . "/" . $key . $query) {
						$tag->setAttribute("src", $newdir . $key . $query);
					}
				}
				foreach($dom->getElementsByTagName("source") as $tag) {
					$query = parse_url($tag->getAttribute("src"), PHP_URL_QUERY);
					if($query != NULL) {
						$query = "?" . $query;
					}
					if($tag->getAttribute("src") == $this->grav["uri"]->rootUrl() . $page->getOriginal()->route() . "/" . $key . $query) {
						$tag->setAttribute("src", $newdir . $key . $query);
					}
				}
				foreach($dom->getElementsByTagName("a") as $tag) {
					$query = parse_url($tag->getAttribute("href"), PHP_URL_QUERY);
					if($query != NULL) {
						$query = "?" . $query;
					}
					if($tag->getAttribute("href") == $this->grav["uri"]->rootUrl() . $page->getOriginal()->route() . "/" . $key . $query) {
						$tag->setAttribute("href", $newdir . $key . $query);
					}
				}
			}
			$html = "";
			foreach($dom->getElementsByTagname("body")[0]->childNodes as $node) {
				$html .= $dom->saveHTML($node);
			}
			$page->rawMarkdown($html);
		}
	}
	public function onPluginsInitialized() {
		if($this->isAdmin()) {
			$this->enable(["onTwigTemplatePaths" => ["onTwigTemplatePaths", 999]]);
		}
	}
	public function onTwigTemplatePaths() {
		if(file_exists(__DIR__ . "/../../data/tinymce-editor/templates")) {
			$this->grav["twig"]->twig_paths[] = __DIR__ . "/../../data/tinymce-editor/templates";
		} else {
			$this->grav["twig"]->twig_paths[] = __DIR__ . "/templates";
		}
	}
	public function onTwigSiteVariables() {
		if($this->isAdmin()) {
			$page = $this->grav["admin"]->page();
			$version = "tinymce";
			if($this->config["plugins"]["tinymce-editor"]["apikey"] == "") {
				if(file_exists(__DIR__ . "/../../data/tinymce-editor/js/" . $version)) {
					$this->grav["assets"]->add("user://data/tinymce-editor/js/" . $version . "/tinymce.min.js");
				} else {
					$this->grav["assets"]->add("plugin://tinymce-editor/js/" . $version . "/tinymce.min.js");
				}
			} else {
				$this->grav["assets"]->add("https://cloud.tinymce.com/4/tinymce.min.js?apiKey=" . urlencode($this->config["plugins"]["tinymce-editor"]["apikey"]));
			}
			if(file_exists(__DIR__ . "/../../data/tinymce-editor/css")) {
				$this->grav["assets"]->add("user://data/tinymce-editor/css/editor.css");
			} else {
				$this->grav["assets"]->add("plugin://tinymce-editor/css/editor.css");
			}
			if($this->config["plugins"]["tinymce-editor"]["restrictions"]["blacklist"]) {
				foreach($this->grav["pages"]->root()->collection($this->config["plugins"]["tinymce-editor"]["blacklist"], false) as $item) {
					if($item == $page) {
						return;
					}
				}
			}
			$whitelisted = true;
			if($this->config["plugins"]["tinymce-editor"]["restrictions"]["whitelist"]) {
				$whitelisted = false;
				foreach($this->grav["pages"]->root()->collection($this->config["plugins"]["tinymce-editor"]["whitelist"], false) as $item) {
					if($item == $page) {
						$whitelisted = true;
						break;
					}
				}
			}
			if($whitelisted) {
				if(file_exists(__DIR__ . "/../../data/tinymce-editor/blueprints")) {
					$this->grav["locator"]->addPath("blueprints", "", __DIR__ . "/../../data/tinymce-editor/blueprints");
				} else {
					$this->grav["locator"]->addPath("blueprints", "", __DIR__ . "/blueprints");
				}
			}
		}
	}
	public function onPageContentProcessed($event) {
		$page = $event["page"];
		if(!$this->isAdmin() && $page->getRawContent() != "") {
			$excerpts = new Excerpts($page);
			$dom = new \DOMDocument("1.0", "UTF-8");
			@$dom->loadHTML(mb_convert_encoding($page->getRawContent(), "HTML-ENTITIES", "UTF-8"), LIBXML_PARSEHUGE);
			foreach($dom->getElementsByTagName("img") as $tag) {
				$excerpt = ["element" => ["attributes" => ["href" => $tag->getAttribute("src")]]];
				$excerpt["element"]["attributes"]["src"] = $excerpts->processLinkExcerpt($excerpt)["element"]["attributes"]["href"];
				$tag->setAttribute("src", $excerpts->processImageExcerpt($excerpt)["element"]["attributes"]["src"]);
			}
			foreach($dom->getElementsByTagName("audio") as $tag) {
				$excerpt = ["element" => ["attributes" => ["href" => $tag->getAttribute("src")]]];
				$tag->setAttribute("src", $excerpts->processLinkExcerpt($excerpt)["element"]["attributes"]["href"]);
			}
			foreach($dom->getElementsByTagName("source") as $tag) {
				$excerpt = ["element" => ["attributes" => ["href" => $tag->getAttribute("src")]]];
				$tag->setAttribute("src", $excerpts->processLinkExcerpt($excerpt)["element"]["attributes"]["href"]);
			}
			foreach($dom->getElementsByTagName("a") as $tag) {
				$excerpt = ["element" => ["attributes" => ["href" => $tag->getAttribute("href")]]];
				$tag->setAttribute("href", $excerpts->processLinkExcerpt($excerpt)["element"]["attributes"]["href"]);
			}
			$html = "";
			foreach($dom->getElementsByTagname("body")[0]->childNodes as $node) {
				$html .= $dom->saveHTML($node);
			}
			$page->setRawContent($html);
		}
	}
}
