<?php
/**
 * Class ITSEC_File_Change_Hash_Comparator_Chain
 */
class ITSEC_File_Change_Hash_Comparator_Chain implements ITSEC_File_Change_Hash_Comparator_Loadable {

	/** @var ITSEC_File_Change_Hash_Comparator[] */
	private $chain;

	/** @var ITSEC_File_Change_Package */
	private $package;

	/**
	 * ITSEC_File_Change_Hash_Comparator_Chain constructor.
	 *
	 * @param ITSEC_File_Change_Hash_Comparator[] $chain
	 */
	public function __construct( array $chain ) {
		$this->chain = $chain;
	}

	/**
	 * Get all the comparators that support a package.
	 *
	 * @param ITSEC_File_Change_Package $package
	 *
	 * @return ITSEC_File_Change_Hash_Comparator[]
	 */
	private function for_package( ITSEC_File_Change_Package $package ) {
		$this->package = $package;

		$supported = array();

		foreach ( $this->chain as $comparator ) {
			if ( $comparator->supports_package( $package ) ) {
				$supported[] = $comparator;
			}
		}

		usort( $supported, array( $this, '_sort' ) );
		$this->package = null;

		return $supported;
	}

	private function _sort( $a, $b ) {

		$a_loadable = $a instanceof ITSEC_File_Change_Hash_Comparator_Loadable;
		$b_loadable = $b instanceof ITSEC_File_Change_Hash_Comparator_Loadable;

		if ( $a_loadable && ! $b_loadable ) {
			return 1;
		} elseif ( ! $a_loadable && $b_loadable ) {
			return - 1;
		} elseif ( $a_loadable && $b_loadable ) {
			return ( $a->get_load_cost( $this->package ) - $b->get_load_cost( $this->package ) );
		}

		return 0;
	}

	/**
	 * @inheritdoc
	 */
	public function supports_package( ITSEC_File_Change_Package $package ) {

		foreach ( $this->chain as $comparator ) {
			if ( $comparator->supports_package( $package ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function has_hash( $relative_path, ITSEC_File_Change_Package $package ) {

		foreach ( $this->chain as $comparator ) {
			if ( $comparator->supports_package( $package ) && $comparator->has_hash( $relative_path, $package ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function hash_matches( $actual_hash, $relative_path, ITSEC_File_Change_Package $package ) {

		foreach ( $this->for_package( $package ) as $comparator ) {
			if ( $comparator->has_hash( $relative_path, $package ) ) {
				return $comparator->hash_matches( $actual_hash, $relative_path, $package );
			}
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function load( ITSEC_File_Change_Package $package ) {
		$e = null;

		foreach ( $this->for_package( $package ) as $comparator ) {
			if ( $comparator instanceof ITSEC_File_Change_Hash_Comparator_Loadable ) {
				try {
					$comparator->load( $package );
				} catch ( ITSEC_File_Change_Hash_Loading_Failed_Exception $e ) {
					continue;
				}
			}

			return;
		}

		if ( $e ) {
			throw $e;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function get_load_cost( ITSEC_File_Change_Package $package ) {
		foreach ( $this->for_package( $package ) as $comparator ) {
			if ( $comparator instanceof ITSEC_File_Change_Hash_Comparator_Loadable ) {
				return $comparator->get_load_cost( $package );
			}

			return 0;
		}

		return 0;
	}
}