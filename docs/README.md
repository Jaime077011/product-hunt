# Product Hunt Quiz Plugin Documentation

## Overview

The Product Hunt Quiz plugin is a powerful WordPress tool that allows store owners to create interactive product recommendation quizzes. These quizzes guide customers through a series of questions and recommend products based on their answers, enhancing the shopping experience and increasing conversions.

## Features

- **Quiz Builder**: Intuitive interface for creating and managing quizzes
- **Question Management**: Support for multiple question types (multiple-choice, checkbox, text, email)
- **Product Mapping**: Associate products with specific answers for personalized recommendations
- **Email Capture**: Collect customer emails before showing quiz results
- **Customization**: Adjust colors, fonts, and styles to match your brand
- **Analytics**: Track quiz completions, popular products, and user engagement
- **Shortcode Integration**: Easily embed quizzes anywhere on your site
- **Time Limits**: Set time constraints for quiz completion
- **Response Limits**: Control the maximum number of submissions per quiz

## Getting Started

### Installation

1. Upload the `product-hunt` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "Product Quizzes" in the admin menu to start creating quizzes

## Quiz Management

### Creating Your First Quiz

1. Go to "Product Quizzes" > "Add New Quiz"
2. Enter a title and description for your quiz
3. Configure basic settings (category, status, time limit, etc.)
4. Customize the appearance using the styling options
5. Add questions and answers using the question builder
6. Map product recommendations to specific answers
7. Configure email capture settings if desired
8. Save your quiz and use the shortcode to display it on your site

### Editing Existing Quizzes

1. Go to "Product Quizzes" to see a list of all your quizzes
2. Hover over the quiz you want to edit and click the "Edit" link
3. Make your desired changes in the quiz editor
4. Click "Update Quiz" to save your changes

> **Note:** If you encounter issues when editing quizzes, try the following:
> - Clear your browser cache and refresh the page
> - Make sure you're using the latest version of the plugin
> - Check that no other plugins are conflicting with the quiz editor
> - If the editor seems stuck, save your work and reload the page

### Troubleshooting Common Issues

#### Edit Quiz Not Working
If you're having trouble with the edit quiz functionality:

1. **Clear Cache**: Clear your browser cache and cookies
2. **Plugin Conflicts**: Temporarily deactivate other plugins to check for conflicts
3. **Browser Issues**: Try using a different browser
4. **Form Reset**: Sometimes the edit form needs to be refreshed. Exit the edit page, return to the quiz list, and try editing again
5. **Console Errors**: Check your browser's developer console for JavaScript errors

## Quiz Settings

### Basic Settings

- **Quiz Title**: The name of your quiz (displayed to users)
- **Quiz Description**: Brief explanation of what the quiz is about
- **Category**: Optional grouping for organizing quizzes
- **Status**: Draft (private) or Published (public)

### Advanced Settings

- **Time Limit**: Set a time constraint in minutes (0 = unlimited)
- **Maximum Responses**: Limit the number of submissions (0 = unlimited)
- **Show Progress Bar**: Toggle the visibility of the quiz progress indicator

### Styling Options

- **Primary Color**: Used for buttons, headers, and accents
- **Secondary Color**: Used for highlights and secondary elements
- **Button Style**: Choose between Rounded, Square, or Rounded Corners
- **Font Family**: Select from common web-safe fonts

### Email Capture Settings

- **Enable Email Capture**: Ask for email before showing results
- **Email Required**: Make providing an email mandatory
- **Email Form Title**: Custom heading for the email capture form
- **Email Form Description**: Explain why you're collecting emails
- **Privacy Notice**: Display a privacy policy message

### Results Display Settings

- **Results Title**: Custom heading for the results page
- **Results Description**: Text displayed above recommended products

## Question Types

The plugin supports four question types:

1. **Multiple Choice**: User selects a single answer from options
2. **Checkboxes**: User can select multiple answers
3. **Text Input**: User provides a free-form text response
4. **Email Input**: Specialized field for collecting email addresses

## Product Mapping

Connect products to specific answers to generate personalized recommendations:

1. Click the cart icon on an answer option
2. Search for products by name or SKU
3. Select products to recommend when this answer is chosen
4. Adjust the recommendation weight (higher = stronger recommendation)

## Shortcode Usage

Embed quizzes in any post, page, or widget using shortcodes:

## Analytics

The Product Hunt Quiz plugin includes comprehensive analytics to track quiz performance and user engagement. To access the analytics dashboard, navigate to "Product Quizzes" > "Analytics" in the WordPress admin menu.

### Analytics Features

#### Filtering & Date Ranges
- Filter analytics by specific quizzes or view aggregated data
- Select from predefined date ranges (7 days, 30 days, 90 days, 1 year)
- Create custom date ranges for precise reporting periods

#### Summary Metrics
- **Quiz Completions**: Total number of completed quizzes in the selected period
- **Completion Rate**: Percentage of started quizzes that were completed
- **Average Completion Time**: Average time users spend completing quizzes
- **Emails Captured**: Number of email addresses collected through quizzes

#### Detailed Reports
- **Completion Trends**: Chart showing quiz completion trends over time
- **Top Performing Quizzes**: Most completed quizzes ranked by popularity
- **Most Recommended Products**: Products most frequently shown in quiz results
- **Popular Questions**: Most answered questions across all quizzes

### Interpreting Your Analytics

#### Completion Rate
- High completion rates (>70%) indicate engaging quiz content
- Low completion rates (<40%) may suggest:
  - Quizzes are too long
  - Questions are confusing
  - Technical issues may exist

#### Product Recommendations
- **Recommendation Count**: How often a product appears in results
- **Click Rate**: Percentage of users who click on a recommended product
- Low click rates may indicate:
  - Poor product-answer mapping
  - Irrelevant recommendations
  - Issues with product presentation

#### Optimizing Based on Analytics

**For Low Completion Rates:**
1. Shorten your quiz by removing less important questions
2. Make questions clearer and more engaging
3. Ensure conditional logic is properly configured

**For Low Product Click Rates:**
1. Review product-answer mappings for relevance
2. Improve product presentation in results
3. Consider adjusting recommendation weights

**For Email Capture:**
1. Make the value proposition clear to increase opt-ins
2. Test different email form headings and descriptions
3. Consider whether requiring email is hurting completion rates

## Settings

The Settings page allows you to configure global settings for the Product Hunt Quiz plugin.

### General Settings
- View plugin version information
- Access documentation

### Style Defaults
- **Default Primary Color**: Set the default primary color for new quizzes
- **Default Secondary Color**: Set the default secondary color for new quizzes
- **Default Button Style**: Choose between Rounded, Square, or Rounded Corners
- **Default Font Family**: Select a default font family for new quizzes

### Email Integration
- **Email Service**: Choose from Mailchimp or custom webhook integration
- **Mailchimp API Key**: Connect to your Mailchimp account
- **Mailchimp List/Audience ID**: Specify which list to add quiz participants to

### Tracking & Analytics
- **Google Analytics Events**: Track quiz interactions in Google Analytics
- **Event Category**: Set the category name for quiz events in Google Analytics

### GDPR & Privacy
- **GDPR Compliance**: Enable additional GDPR compliance features
- **Consent Message**: Customize the consent message shown to users
- **Privacy Policy Page**: Link to your site's privacy policy

### Performance
- **Cache Results**: Enable caching of quiz results for better performance
- **Cache Duration**: Set how long to cache results (in hours)

### Permissions
- **Editor Access**: Allow Editor users to create and manage quizzes

## Advanced Usage

### Programmatic Quiz Creation

Developers can programmatically create quizzes using the plugin's API:

```php
$new_quiz = array(
    'title'       => 'My Programmatic Quiz',
    'description' => 'Created via code',
    'settings'    => array(
        'primary_color' => '#3498db',
        'time_limit'    => 5,
        // other settings
    )
);

// Create the quiz
$quiz_id = product_hunt_create_quiz($new_quiz);
```

### Extending the Plugin

The Product Hunt plugin architecture is extensible through WordPress filters and actions. See our developer documentation for more information on available hooks.

