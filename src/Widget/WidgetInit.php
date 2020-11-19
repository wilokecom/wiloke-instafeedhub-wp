<?php

namespace InstafeedHub\Widget;

use InstafeedHub\Helpers\Widget;

/**
 * Class WidgetInit
 * @package InstafeedHub\Widget
 */
class WidgetInit
{
	public function __construct()
	{
		add_action('wp_ajax_save_instagram_widget', [$this, 'saveInstagramWidget']);
		add_action('widgets_init', [$this, 'register']);
		add_action('admin_enqueue_scripts', [$this, 'handleWidget']);
	}

	public function register()
	{
		register_widget('InstafeedHub\Widget\InstagramFeedhub');
	}

	public function handleWidget()
	{
		global $pagenow;

		if ($pagenow == 'widgets.php') {
			wp_enqueue_script(
				'handle-widget',
				IFH_ASSETS . 'js/handle-widget.js',
				[],
				IFH_VERSION,
				true
			);
			// === EDEN TUAN JS === //
			wp_enqueue_script(
				'instafeedhub-fokedJs',
				IFH_ASSETS . 'forWidget/foked.js',
				[],
				IFH_VERSION,
				true
			);
			wp_enqueue_style(
				'instafeedhub-fokedCss',
				IFH_ASSETS . 'forWidget/foked.css',
				[],
				IFH_VERSION,
				'all'
			);
			wp_localize_script('jquery', 'instafeedHubElements', $this->getInstafeedHubElements());
		}
	}

	/**
	 * @return array|object
	 */
	public function getInstafeedHubElements()
	{
		$aWidgetIDs = Widget::getWidgetIDsByBaseID();
		if (empty($aWidgetIDs)) {
			return (object)[];
		}
		$aElements = [];
		$aInstaWidget = get_option('widget-insta-feedhub');
		foreach ($aWidgetIDs as $widgetID) {
			$element = explode('-', $widgetID);
			$index = intval(end($element));
			$instaID = ($aInstaWidget[$index]['instaId'] == null) ? '' : $aInstaWidget[$index]['instaId'];
			$instaTitle = ($aInstaWidget[$index]['instaTitle'] == null) ? '' : $aInstaWidget[$index]['instaTitle'];
			$aElements[$widgetID] = [
				'widgetID'       => $widgetID,
				'buttonID'       => 'widget-' . $widgetID . '-button',
				'instagramID'    => $instaID,
				'instagramTitle' => $instaTitle
			];
		}

		return $aElements;
	}

	/**
	 * @return bool
	 */
	public function saveInstagramWidget()
	{
		if (!isset($_POST['action']) || $_POST['action'] !== 'save_instagram_widget') {
			return false;
		}

		$aInstaWidget = get_option('widget-insta-feedhub');
		$widgetID = $_POST['widgetID'];
		$element = explode('-', $widgetID);
		$index = intval(end($element));
		$aInstaWidget[$index] = [
			'widgetID'   => $widgetID,
			'instaId'    => $_POST['instaId'],
			'instaTitle' => $_POST['instaTitle'],
		];

		update_option('widget-insta-feedhub', $aInstaWidget);
	}
}