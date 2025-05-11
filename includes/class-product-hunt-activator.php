<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class Product_Hunt_Activator {

    /**
     * Create the database tables needed for the plugin.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
    }

    /**
     * Create the database tables for storing quizzes, questions, answers,
     * user responses, and product recommendations.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_prefix = $wpdb->prefix . 'ph_'; // 'ph' stands for Product Hunt

        // Table: Quizzes
        $table_quizzes = $table_prefix . 'quizzes';
        $sql_quizzes = "CREATE TABLE $table_quizzes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            category VARCHAR(100) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            author_id BIGINT(20) UNSIGNED NOT NULL,
            settings LONGTEXT NULL,
            PRIMARY KEY (id),
            KEY author_id (author_id)
        ) $charset_collate;";

        // Table: Questions
        $table_questions = $table_prefix . 'questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT(20) UNSIGNED NOT NULL,
            parent_question_id BIGINT(20) UNSIGNED NULL,
            parent_answer_id BIGINT(20) UNSIGNED NULL,
            question_text TEXT NOT NULL,
            question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
            question_order INT(11) NOT NULL DEFAULT 0,
            is_required TINYINT(1) NOT NULL DEFAULT 1,
            settings LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY parent_question_id (parent_question_id),
            KEY parent_answer_id (parent_answer_id)
        ) $charset_collate;";

        // Table: Answers
        $table_answers = $table_prefix . 'answers';
        $sql_answers = "CREATE TABLE $table_answers (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id BIGINT(20) UNSIGNED NOT NULL,
            answer_text TEXT NOT NULL,
            answer_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) $charset_collate;";

        // Table: User Data
        $table_user_data = $table_prefix . 'user_data';
        $sql_user_data = "CREATE TABLE $table_user_data (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NULL,
            email VARCHAR(255) NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email)
        ) $charset_collate;";

        // Table: User Responses
        $table_user_responses = $table_prefix . 'user_responses';
        $sql_user_responses = "CREATE TABLE $table_user_responses (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_data_id BIGINT(20) UNSIGNED NOT NULL,
            quiz_id BIGINT(20) UNSIGNED NOT NULL,
            question_id BIGINT(20) UNSIGNED NOT NULL,
            answer_id BIGINT(20) UNSIGNED NULL,
            custom_answer TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_data_id (user_data_id),
            KEY quiz_id (quiz_id),
            KEY question_id (question_id),
            KEY answer_id (answer_id)
        ) $charset_collate;";

        // Table: Product Recommendations
        $table_product_recommendations = $table_prefix . 'product_recommendations';
        $sql_product_recommendations = "CREATE TABLE $table_product_recommendations (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            answer_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NULL,
            product_cat_id BIGINT(20) UNSIGNED NULL,
            weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY answer_id (answer_id),
            KEY product_id (product_id),
            KEY product_cat_id (product_cat_id)
        ) $charset_collate;";

        // Table: Quiz Performance Analytics
        $table_quiz_performance = $table_prefix . 'quiz_performance';
        $sql_quiz_performance = "CREATE TABLE $table_quiz_performance (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT(20) UNSIGNED NOT NULL,
            starts INT(11) NOT NULL DEFAULT 0,
            completions INT(11) NOT NULL DEFAULT 0,
            abandonment_rate DECIMAL(5,2) NULL,
            avg_completion_time INT(11) NULL,
            last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY quiz_id (quiz_id)
        ) $charset_collate;";

        // Table: Result Interactions
        $table_result_interactions = $table_prefix . 'result_interactions';
        $sql_result_interactions = "CREATE TABLE $table_result_interactions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_data_id BIGINT(20) UNSIGNED NOT NULL,
            quiz_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            interaction_type VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_data_id (user_data_id),
            KEY quiz_id (quiz_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        // Table: Conditional Logic
        $table_conditional_logic = $table_prefix . 'conditional_logic';
        $sql_conditional_logic = "CREATE TABLE $table_conditional_logic (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT(20) UNSIGNED NOT NULL,
            if_question_id BIGINT(20) UNSIGNED NOT NULL,
            if_answer_id BIGINT(20) UNSIGNED NOT NULL,
            then_question_id BIGINT(20) UNSIGNED NOT NULL,
            comparison VARCHAR(20) NOT NULL DEFAULT 'equals',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY if_question_id (if_question_id),
            KEY if_answer_id (if_answer_id),
            KEY then_question_id (then_question_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create the tables
        dbDelta($sql_quizzes);
        dbDelta($sql_questions);
        dbDelta($sql_answers);
        dbDelta($sql_user_data);
        dbDelta($sql_user_responses);
        dbDelta($sql_product_recommendations);
        dbDelta($sql_quiz_performance);
        dbDelta($sql_result_interactions);
        dbDelta($sql_conditional_logic);

        // Store the database version
        add_option('product_hunt_db_version', PRODUCT_HUNT_VERSION);
    }
}