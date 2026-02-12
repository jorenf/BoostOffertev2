<?php
/**
 * Offerte List Page view.
 *
 * @package Boost_Offerte
 * @var BoostOfferte\Admin\Offerte_List_Table $list_table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bs-offerte-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Offertes', 'boost-offerte' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bs-offerte-edit' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Nieuwe Offerte', 'boost-offerte' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php $list_table->views(); ?>

    <form method="get">
        <input type="hidden" name="page" value="bs-offertes">
        <?php
        $list_table->search_box( __( 'Zoeken', 'boost-offerte' ), 'offerte-search' );
        $list_table->display();
        ?>
    </form>
</div>
