<?php

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 */
class Product_Hunt_Deactivator {

    /**
     * Processing to perform on deactivation
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Nothing to do on deactivation right now
        // Note: We're not removing the database tables on deactivation to prevent data loss
    }
}