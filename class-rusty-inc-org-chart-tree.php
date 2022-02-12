<?php

/**
 * Represents an org-chart tree
 */
class Rusty_Inc_Org_Chart_Tree {
	private $list_of_teams;
	private $map_of_teams;

	/**
	 * @param array $list_of_teams an array of teams, where each team is an associative array with at least an `id` and `parent_id` keys
	 */
	public function __construct( $list_of_teams ) {
		$this->list_of_teams = $list_of_teams;
		$this->build_map_of_teams();
	}

	/**
	 * Converts the internal representation to a nested representation, for which:
	 * - each node is an associative array with at least the following keys:
	 *   - `id`
	 *   - `children`: an array of the children of the node, each of them a node by itself
	 * - the whole tree is represented by the root
	 *
	 * @return array|null the root of the tree or `null` if the tree is empty
	 */
	public function get_nested_tree( $root = null ) {
		try {
			if ( is_null( $root ) ) {
				$root = $this->get_root( $this->list_of_teams );
				if ( is_null( $root ) ) {
					return null;
				}
			}
			$root['children'] = array();
			if ( $this->map_of_teams === null ) {
				return $root;
			}
			if ( array_key_exists( $root['id'], $this->map_of_teams ) ) {
				$children = $this->map_of_teams[$root['id']];
				if ( ! is_null( $children ) && ! empty( $children ) ) {
					foreach ( $children as $child ) {
						array_push( $root['children'], $this->get_nested_tree( $child ) );
					}
				}
			}
			return $root;
		} catch ( Exception $ex ) {
			throw $ex;
		}
	}

	private function build_map_of_teams() {
		try {
			foreach ( $this->list_of_teams as $team ) {
				if ( ! is_null( $team['parent_id'] ) ) {
					// root is not inserted in the map
					$this->map_of_teams[$team['parent_id']][] = $team;
				}
			}
		} catch ( Exception $ex ) {
			throw $ex;
		}
	}

	public function get_nested_tree_js( $root = null ) {
		try {
		  	return json_encode( $this->get_nested_tree( $root ) );
		} catch ( Exception $ex ) {
			throw $ex;
		}
	}

	private function get_root( $tree ) {
		try {
			foreach ( $tree as $team ) {
				if ( is_null( $team['parent_id'] ) ) {
					return $team;
				}
			}
			return null;
		} catch ( Exception $ex ) {
			throw $ex;
		}
	}

	private function emoji_to_js( $emoji ) {
		return '"' . implode(
			'',
			array_map(
				function( $utf16 ) {
					return '\u' . str_pad( strtolower( sprintf( '%X', $utf16 ) ), 4, '0', STR_PAD_LEFT );
				},
				$this->emoji_to_utf16_surrogate( $this->utf8_ord( $emoji ) )
			)
		) . '",';
	}

	private function emoji_to_utf16_surrogate( $emoji ) {
		if ( $emoji > 0x10000 ) {
			return [ ( ( $emoji - 0x10000 ) >> 10 ) + 0xD800, ( ( $emoji - 0x10000 ) % 0x400 ) + 0xDC00 ];
		} else {
			return [ $emoji ];
		}
	}

	private function utf8_ord( $emoji ) {
		$first_byte = ord( $emoji[0] );
		if ( $first_byte >= 0 && $first_byte <= 127 ) {
			return $first_byte;
		}
		$second_byte = ord( $emoji[1] );
		if ( $first_byte >= 192 && $first_byte <= 223 ) {
			return ( $first_byte - 192 ) * 64 + ( $second_byte - 128 );
		}
		$third_byte = ord( $emoji[2] );
		if ( $first_byte >= 224 && $first_byte <= 239 ) {
			return ( $first_byte - 224 ) * 4096 + ( $second_byte - 128 ) * 64 + ( $third_byte - 128 );
		}
		$fourth_byte = ord( $emoji[3] );
		if ( $first_byte >= 240 && $first_byte <= 247 ) {
			return ( $first_byte - 240 ) * 262144 + ( $second_byte - 128 ) * 4096 + ( $third_byte - 128 ) * 64 + ( $fourth_byte - 128 );
		}
		return false;
	}
}
