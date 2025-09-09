<?php

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 */
class EDM_Deactivator {

    /**
     * Main deactivation method.
     *
     * This can be used to clean up temporary data, but we won't remove
     * roles or tables on deactivation, only on uninstallation.
     */
    public static function deactivate() {
        // Optional: Add any cleanup code needed on deactivation.
        // For now, we leave data and roles intact.
    }
}
