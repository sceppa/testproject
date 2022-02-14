<?php

if ( file_exists( __DIR__ . '/class-rusty-inc-org-chart-tree.php' ) ) {
	require_once __DIR__ . '/class-rusty-inc-org-chart-tree.php';
}
if ( file_exists( __DIR__ . '/class-rusty-inc-org-chart-sharing.php' ) ) {
	require_once __DIR__ . '/class-rusty-inc-org-chart-sharing.php';
}

/**
 * Responsible for the WordPress plumbing -- getting the page running, output of JS
 */
class Rusty_Inc_Org_Chart_Plugin {

	protected $sharing;
	public $response_message;

	public const OPTION_NAME       = 'rusty-inc-org-chart-tree';
	public const DEFAULT_ORG_CHART = [
		[
			'id'        => 1,
			'name'      => 'Rusty Corp.',
			'emoji'     => '🐕',
			'parent_id' => null,
		],
		[
			'id'        => 2,
			'name'      => 'Food',
			'emoji'     => '🥩',
			'parent_id' => 1,
		],
		[
			'id'        => 3,
			'name'      => 'Canine Therapy',
			'emoji'     => '😌',
			'parent_id' => 1,
		],
		[
			'id'        => 4,
			'name'      => 'Massages',
			'emoji'     => '💆',
			'parent_id' => 3,
		],
		[
			'id'        => 5,
			'name'      => 'Games',
			'emoji'     => '🎾',
			'parent_id' => 3,
		],
	];

	public function __construct() {
		$this->sharing = new Rusty_Inc_Org_Chart_Sharing();
	}

	/**
	 * Registers the initial hooks to get the plugin going, if you're
	 * curious, see https://developer.wordpress.org/plugins/hooks/
	 *
	 * In short, both actions and filters are like events or callbacks. The difference
	 * between them is that the return value from filters is passed to the next callback,
	 * while the return value of actions is ignored. In this plugin we're using mostly actions.
	 */
	public function add_init_action() {
		/* a plugin shouldn't do anything before the "init" hook,
		 * that's why the main initialization code is in the init() method
		 */
		//$this->check_access();
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Executed on the "init" WordPress action -- initializes the bulk
	 * of our hooks
	 */
	public function init() {
		//update_option( self::OPTION_NAME, self::DEFAULT_ORG_CHART );
		$page_hook_suffix = null;

		/* Registers the UI for "Rusty Inc. Org Chart" page linked from the main
		 * wp-admin menu
		 * @see https://developer.wordpress.org/reference/functions/add_menu_page/
		 */
		add_action(
			'admin_menu',
			function() use ( &$page_hook_suffix ) {
				$position         = 2; // this means the second one from the top
				$page_hook_suffix = add_menu_page( 'Rusty Inc. Org Chart', 'Rusty Inc. Org Chart', 'publish_posts', 'rusty-inc-org-chart', array( $this, 'org_chart_controller' ), 'dashicons-heart', $position );
				add_action( "admin_footer-{$page_hook_suffix}", [ $this, 'scripts_in_footer' ] );
			}
		);

		/**
		 * Handles routing for the publicly shared page -- only triggered when
		 * we have the right arguments in the URL
		 */
		if ( $this->sharing->does_url_have_valid_key() ) {
			$this->org_chart_controller();
			$this->scripts_in_footer();
			exit;
		}

		/**
		 * Store the tree
		 */
		try {
			if ( isset( $_POST['tree']) && $_POST['tree'] !== null  && !empty( $_POST['tree'] )) {
				$tree_array = json_decode( stripslashes( $_POST['tree'] ), true );
				if ( is_array( $tree_array ) ) {
					$this->build_array( $tree_array );
					update_option( self::OPTION_NAME, $this->tmp_array );
					$this->response_message = 'Tree stored succesfully';
				} else {
					$this->response_message = "Error: wrong tree format submitted."; 		
				}
			} 
		} catch ( Exception $ex ) {
			$this->response_message = "Error happened storing the tree: $ex->getMessage()."; 
		}

		/**
		 * Store the key
		 */
		try {
			if ( isset( $_POST['key']) && 'null' === $_POST['key']  ) {
				$this->sharing->regenerate_key();
				$this->response_message .=  ( empty( $this->response_message ) ?: ' - ' ) . 'Key updated succesfully with value ' . $this->sharing->key();
			}

		} catch ( Exception $ex ) {
			$this->response_message = ( empty( $this->response_message ) ?: ' - ' ). "Error happened storing the key: $ex->getMessage();"; 
		}
	}

	/**
	 * Outputs script tags right before closing </body> tag
	 *
	 * We want it in the footer to avoid having to hook on document.onload -- all the DOM we need is
	 * already loaded by now.
	 *
	 * While WordPress has a system to load JavaScript assets, unfortunately it still doesn't support
	 * the ES6 `type=module` convention, so we chose to print the script tags manually in the footer.
	 */
	public function scripts_in_footer() {
		$tree             = new Rusty_Inc_Org_Chart_Tree( get_option( self::OPTION_NAME, self::DEFAULT_ORG_CHART ) );
		try {
			$tree_js          = $tree->get_nested_tree_js();
		} catch ( Exception $ex ) {
			$this->response_message = ( empty( $this->response_message ) ?: ' - ' ). "Error happened building the tree: $ex->getMessage();"; 
		}
		$response_message = $this->response_message;
		$ui_js_url        = plugins_url( 'ui.js', __FILE__ );
		$framework_js_url = plugins_url( 'framework.js', __FILE__ );
		$secret_url       = $this->sharing->url();
		if ( file_exists( __DIR__ . '/admin-page-inline-script.php' ) ) {
			require __DIR__ . '/admin-page-inline-script.php';
		} else {
			die(  __DIR__ . '/admin-page-inline-script.php not found.' );
		}
	}

	/**
	 * Callback for add_menu_page() -- outputs the HTML for our org chart UI
	 */
	public function org_chart_controller() {
		require __DIR__ . '/admin-page-template.php';
	}

	private $tmp_array = array();
	private function build_array( array $input ) {
		try {
			if ( is_null( $input ) || count( $input ) === 0 ) {
				return;
			}		
			if ( array_key_exists( 'children', $input ) ) {
				if ( empty( $input['children'] ) ) {
					unset($input['children']);
					array_push( $this->tmp_array, $input );
				} else {
					foreach ($input['children'] as $child ) {
						$this->build_array( $child );
					}
					unset( $input['children'] );
					array_push( $this->tmp_array, $input );
				}
			}
		} catch ( Exception $ex ) {
			throw $ex;
		}
	}
}