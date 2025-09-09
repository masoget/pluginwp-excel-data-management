## Fix Summary: Permission Checks for Public Shortcodes

### Issue
Public shortcodes were displaying data regardless of user permissions, and there was no check against the plugin's `min_role_view` setting.

### Solution
1. Added permission checks to all three shortcode functions:
   - `display_excel_data`
   - `search_excel_data` 
   - `form_excel_data`

2. Created a helper function `user_can_view_data()` that:
   - Retrieves plugin settings
   - Checks user's role against the minimum required role
   - Respects WordPress role hierarchy
   - Handles both logged-in and non-logged-in users

### Expected Behavior
With the current setting of `min_role_view = subscriber`:
- Logged-in users with any role can view data
- Non-logged-in users can also view data (subscriber-level access allows public viewing)

If the setting were changed to a higher role (e.g., `editor`):
- Only logged-in users with editor or administrator roles could view data
- Non-logged-in users would be denied access