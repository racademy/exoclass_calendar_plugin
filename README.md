# ExoClass Calendar WordPress Plugin

A beautiful calendar plugin for displaying fitness classes and activities from ExoClass API with filtering capabilities.

## Features

- **Weekly Calendar View**: Display classes in a weekly grid format
- **Real-time API Integration**: Fetches live data from ExoClass API
- **Advanced Filtering**: Filter by location, activity type, age group, difficulty level, and availability
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Interactive Events**: Click on events to open group management pages
- **Visual Activity Icons**: Beautiful gradients and icons for different activity types
- **Availability Status**: Shows available spots, limited spots, or full classes
- **Lithuanian Localization**: Calendar in Lithuanian with proper timezone support

## Installation

1. Upload the `exoclass-calendar` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the shortcode `[exoclass_calendar]` in any post or page

## Usage

### Basic Usage

Simply add the shortcode to any post or page:

```
[exoclass_calendar]
```

### Advanced Usage

You can customize the calendar with parameters:

```
[exoclass_calendar height="600px" aspect_ratio="2.0"]
```

**Available Parameters:**
- `height`: Set the calendar height (default: "auto")
- `aspect_ratio`: Set the aspect ratio (default: "1.8")

## Configuration

The plugin is pre-configured to work with the ExoClass test API. To change the API configuration, edit the `exoclass-calendar.php` file and update the `API_CONFIG` array in the `wp_localize_script` function.

## API Configuration

The plugin uses the following API endpoints:
- Base URL: `https://test.api.exoclass.com/api/v1/en`
- Provider Key: `af6791ea-6262-4705-a78c-b7fdc52aec6a`

## Features in Detail

### Filtering System
- **Location Filter**: Filter classes by specific locations
- **Activity Filter**: Filter by activity types (yoga, dance, strength, etc.)
- **Age Group Filter**: Filter by age groups
- **Difficulty Level Filter**: Filter by difficulty levels
- **Availability Filter**: Show only available spots or full classes

### Event Display
- **Custom Event Cards**: Beautiful card design with activity images
- **Status Indicators**: Visual indicators for availability status
- **Teacher Information**: Display instructor names
- **Time Formatting**: Proper Lithuanian time formatting
- **Hover Effects**: Interactive hover effects for related events

### Responsive Design
- **Mobile Optimized**: Fully responsive design for mobile devices
- **Touch Friendly**: Optimized for touch interactions
- **Flexible Layout**: Adapts to different screen sizes

## File Structure

```
exoclass-calendar/
├── exoclass-calendar.php          # Main plugin file
├── assets/
│   ├── css/
│   │   └── calendar.css           # Styles for the calendar
│   └── js/
│       └── calendar.js            # JavaScript functionality
└── README.md                      # This file
```

## Dependencies

- **FullCalendar.js**: Calendar library (loaded from CDN)
- **jQuery**: JavaScript library (WordPress default)
- **WordPress AJAX**: For server-side communication

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Internet Explorer 11+

## Troubleshooting

### Calendar Not Loading
1. Check if the shortcode is properly placed in your content
2. Verify that the plugin is activated
3. Check browser console for JavaScript errors
4. Ensure your theme includes jQuery

### API Errors
1. Check if the ExoClass API is accessible
2. Verify the provider key is correct
3. Check network connectivity
4. Review browser console for API error messages

### Styling Issues
1. Check if your theme CSS conflicts with the calendar styles
2. Verify that the CSS file is loading properly
3. Check for CSS specificity issues

## Support

For support and questions, please contact the plugin developer.

## Changelog

### Version 1.0.0
- Initial release
- Full calendar functionality
- API integration
- Filtering system
- Responsive design

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- FullCalendar.js for the calendar functionality
- ExoClass API for the data source
- WordPress for the platform 