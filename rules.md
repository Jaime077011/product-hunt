Project Rules and Behavior Guidelines for Plugin Development
1. Code Quality and Best Practices
Consistency: Follow WordPress coding standards for PHP, HTML, CSS, and JavaScript. Use consistent indentation (4 spaces) and naming conventions.

Documentation: Every function, class, and method should be well-documented with clear docstrings. Use comments for complex logic or code that may need clarification.

Readability: Write clean, readable code. Avoid long functions or deeply nested conditions. Break down large functions into smaller, manageable parts.

Error Handling: Handle errors gracefully, ensuring that the plugin doesn't break if something goes wrong. Use try-catch where necessary.

Security: Always sanitize and validate user input, especially for quiz data, emails, and product recommendations. Use sanitize_text_field(), sanitize_email(), and esc_html() as needed.

Performance: Optimize database queries, especially when fetching large product catalogs or processing large amounts of quiz data. Avoid unnecessary database calls.

2. Database and Data Structure
Normalization: Maintain a normalized database structure. Avoid redundant data storage across multiple tables.

Relationships: Use foreign keys to link related data (e.g., quizzes to questions, answers to products). Ensure referential integrity.

Indexes: Ensure that frequently queried columns (such as quiz IDs, user responses) are indexed for faster retrieval.

Backup and Recovery: Implement database backup strategies. Always test the backup and recovery process.

3. User Interface (UI) and Experience (UX)
Admin Interface: The admin dashboard should be intuitive and user-friendly. Admins should be able to add, edit, and manage quizzes and questions easily.

Responsive Design: Ensure that both the frontend quiz interface and admin dashboard are mobile-friendly and responsive across all devices.

Customization: Allow admins to easily customize quiz styles, including fonts, colors, and button styles. Ensure the quiz matches the store's branding.

Progressive Disclosure: For conditional logic, display only the relevant questions to users based on their previous answers. Do not overwhelm users with too many options at once.

Error Feedback: Provide clear error messages if a user’s input is invalid or incomplete. Include a "retry" or "back" option for correcting answers.

4. Functionality and Features
Quiz Creation: Admins should be able to create quizzes, add questions (single or multiple choice), and define conditional logic between answers.

Provide easy-to-use drag-and-drop question builders.

Ensure that admins can map answers to product recommendations dynamically.

Quiz Editing: Implement robust quiz editing functionality that cleanly loads existing quiz data.

Ensure edit forms correctly populate all values from the database.

Handle form submission differently for edit vs. create operations to prevent conflicts.

Maintain separation between add and edit states through proper state management.

Conditional Logic: Implement flexible conditional logic so that questions and results are shown based on users' answers.

Ensure conditional logic flows logically (i.e., if a user answers "Yes," show Question 2; if "No," show Question 3).

Product Recommendations: Map each answer to specific products or product categories. Display the product recommendations clearly, with images, names, and "Add to Cart" buttons.

Show products in a visually appealing layout that matches the store's design.

Email Capture: Before showing quiz results, prompt users to enter their email address. Ensure proper validation and integration with email marketing platforms (e.g., Mailchimp).

After capturing email, send an automated confirmation email with a discount code or other incentive.

Analytics: Collect data on quiz completions, product clicks, and user demographics (if provided). Provide simple, actionable analytics for the admin.

Implement graphs (bar/pie charts) to show quiz performance and popular products.

5. Compatibility and Integration
WooCommerce Compatibility: Ensure that the plugin is fully compatible with WooCommerce and interacts with product catalogs, product variations, and categories.

Theme Compatibility: The quiz should work across various WordPress themes. Test with at least three popular themes to ensure cross-theme compatibility.

Plugin Integration: If integrating with other plugins (e.g., Mailchimp for email collection), ensure that these integrations are seamless. Provide easy-to-follow setup instructions for admins.

6. Testing and Debugging
Unit Tests: Write unit tests for key functions, such as quiz result calculation, email capture, and product recommendations.

End-to-End Tests: Simulate quiz interactions to ensure that conditional logic is working as expected. Test that products are correctly recommended based on quiz answers.

Cross-Browser Testing: Ensure that the frontend quiz interface works correctly across different browsers (Chrome, Firefox, Safari, Edge).

Debugging: Use the WP_DEBUG mode for debugging during development. Ensure all errors and warnings are logged and can be reviewed easily by the developer.

7. Security
Sanitize Input: Sanitize and validate all user input. Always use WordPress functions like sanitize_text_field(), sanitize_email(), and esc_url().

Nonces: Use nonces to protect forms and prevent CSRF (Cross-Site Request Forgery) attacks.

User Permissions: Ensure that only users with proper roles (e.g., admin) can manage quizzes, questions, and answers. Prevent unauthorized access to sensitive plugin data.

8. Documentation and Support
Plugin Documentation: Write clear, comprehensive documentation for both developers and administrators. Include:

Setup instructions.

How to create and manage quizzes.

How to customize quiz styles.

How to integrate with email marketing services.

Support: Provide clear contact information for support and frequently asked questions (FAQs). Ensure there is an easy way for users to report issues or request help.

9. Versioning and Updates
Version Control: Use Git for version control to track all changes made to the plugin. Follow semantic versioning (e.g., 1.0.0 for the first stable release, 1.1.0 for minor updates, etc.).

Plugin Updates: Regularly release updates for bug fixes, security patches, and new features. Ensure the update process is smooth for users.

Changelog: Maintain a changelog that details the changes made in each version, including new features, fixes, and improvements.

10. Licensing and Terms
Licensing: If you're offering a paid version of the plugin, ensure clear licensing terms are displayed. Include renewal terms, support duration, and usage rights.

Privacy Policy: Ensure that your plugin complies with GDPR and other relevant data protection laws. Clearly outline how user data (email addresses, quiz responses) is handled in the plugin’s privacy policy.

11. Bug Prevention and Maintenance
State Management: Implement clear state management between creation and editing modes to prevent conflicts.

Use different handlers and entry points for creating new quizzes vs. editing existing ones.

Add safeguards against accidental form resets during editing.

Form Submission: Ensure form submission handlers check for existing quiz IDs before determining whether to update or insert data.

Always validate nonces separately for different operations to maintain security.

Error Detection: Implement comprehensive error logging and detection for quiz operations.

Consider adding a debug mode for administrators to help trace issues during plugin operation.

Interface Validation: Before submitting quiz forms, use client-side validation to ensure that all required fields are completed.

Provide clear error messages specifically identifying which fields need attention.

Regression Testing: Before releasing any updates, test all core functionality including both quiz creation and editing to ensure no regressions occur.

Maintain a suite of test cases covering common use patterns.

