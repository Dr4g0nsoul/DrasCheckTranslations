# 1.0.0
- Initial release
- Command to check snippet translations

# 1.1.0
- Renamed check snippet command to accomodate for the new entity check command
- Added command to check for entity translations
- Removed boilerplate code

# 1.1.1
- Removed the need to input the base language locale (is now retrieved automatically)
- Fields which are empty in the base language when checking for missing entity translations will be skipped (to prevent empty fields from showing up)
- Base language removed from entity transaltion table (is always empty anyways)
- Completely removed boilerplate code
- Added Shopware 6.6 compatibility