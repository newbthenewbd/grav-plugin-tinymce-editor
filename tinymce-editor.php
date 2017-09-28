<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class TinyMCEEditorPlugin extends Plugin {
	public static function getSubscribedEvents() {
		return ['onPluginsInitialized' => ['onPluginsInitialized', 0], 'onTwigSiteVariables' => ['onTwigSiteVariables', 0]];
	}
	public function onPluginsInitialized() {
		if($this->isAdmin()) {
			$this->grav['locator']->addPath('blueprints', '', __DIR__ . DS . 'blueprints');
			$this->enable(['onTwigTemplatePaths' => ['onTwigTemplatePaths', 999]]);
		}
	}
	public function onTwigTemplatePaths() {
		$this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
	}
	public function onTwigSiteVariables() {
		if($this->isAdmin() && strpos($this->grav['uri']->route(), $this->config['plugins']['admin']['route'] . '/pages/') !== false) {
			if($this->config['plugins']['tinymce-editor']['apikey'] === '') {
				$this->grav['assets']->add('plugin://tinymce-editor/js/tinymce/tinymce.min.js');
			} else {
				$this->grav['assets']->add('https://cloud.tinymce.com/stable/tinymce.min.js?apiKey=' . urlencode($this->config['plugins']['tinymce-editor']['apikey']));
			}
			$this->grav['assets']->add('plugin://tinymce-editor/css/editor.css');
		}
	}
}
