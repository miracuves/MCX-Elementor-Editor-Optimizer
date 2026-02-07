# 🔧 Widget Usage Testing & Troubleshooting Guide

## ✅ **Testing Your Widget Usage Analytics**

### **Step 1: Enable Debug Mode**
1. Go to **Settings → Elementor Optimizer**
2. Check **"Enable debug logging"**  
3. Save settings

### **Step 2: Test the Scan Function**
1. Click **"Scan Widget Usage Now"** button
2. Check browser console (F12) for debug messages
3. Look for messages like:
   - "Scan button found: 1"
   - "Found checkboxes: X"  
   - "Scan response: {...}"

### **Step 3: Verify Data Collection**
The scan should show in the browser console:
```javascript
Found X posts with Elementor data
Post 123 (Homepage) uses widgets: heading, image, button
Unique widgets found: heading, image, button, text-editor
```

### **Step 4: Test Select All Functionality**
1. Look for unused widgets section (red box)
2. Click **"Select All"** button  
3. Console should show: "Select all clicked" and "Found checkboxes: X"
4. All checkboxes should become checked

## 🔍 **Troubleshooting Common Issues**

### **Issue 1: No Widgets Found**
**Symptoms**: Scan completes but shows 0 widgets found

**Causes & Solutions**:
- **Elementor not loaded**: Install and activate Elementor
- **No Elementor pages**: Create at least one page with Elementor
- **Permission issues**: Make sure you're logged in as administrator

**Debug Steps**:
1. Check WordPress error log for messages like:
   ```
   Elementor Editor Optimizer: Starting full widget usage scan
   Elementor Editor Optimizer: Found 5 posts with Elementor data
   ```

### **Issue 2: Select All Not Working**
**Symptoms**: "Select All" button doesn't check any boxes

**Debug Steps**:
1. Open browser console (F12)
2. Click "Select All"
3. Look for console messages:
   ```javascript
   Select all clicked
   Found checkboxes: 0
   ```

**If checkboxes = 0**:
- No unused widgets found (all widgets are being used)
- Scan hasn't been run yet
- JavaScript errors preventing detection

### **Issue 3: JavaScript Errors**
**Symptoms**: Buttons don't work, console shows errors

**Common Errors & Fixes**:
```javascript
// Error: eeo_data is not defined
// Fix: Clear browser cache, check admin scripts are loading

// Error: Cannot read property of undefined
// Fix: Wait for page to fully load before testing
```

## 🛠️ **Manual Testing Steps**

### **Test 1: Check Database Storage**
In WordPress admin, go to **Tools → Site Health → Info → Database**
Look for these options (they should exist after running a scan):
- `eeo_widget_usage_data`
- `eeo_widget_usage_log` 
- `eeo_last_full_scan`

### **Test 2: Create Test Content**
1. Create a new page with Elementor
2. Add these widgets: Heading, Image, Button
3. Save the page
4. Run widget scan
5. These widgets should appear in "Used Widgets"

### **Test 3: Verify Widget Detection**
1. Go to **Elementor → Settings → Advanced → CSS Print Method** 
2. Note which widgets show in the main widget list
3. Compare with "Used" vs "Unused" in analytics
4. Used widgets should be GREEN, unused should be RED

## 📊 **Expected Behavior**

### **After First Scan**:
- **Dashboard shows**: X used widgets, Y unused widgets
- **Used widgets**: GREEN background with ✓ Used
- **Unused widgets**: RED background with ⚡ Not Used
- **Console logs**: Detailed scan information

### **After Selecting Widgets**:
- **Checkboxes**: Selected widgets checked
- **Button updates**: "Disable Selected Widgets (X)"
- **Main form**: Corresponding checkboxes checked automatically

### **Performance Improvement**:
```
Before: 50+ widgets loaded = 3-5 second editor load time  
After:  15 used widgets   = 1-2 second editor load time
Result: 50-60% speed improvement
```

## 🚨 **Quick Fixes**

### **Fix 1: Reset Everything**
1. Click **"Reset Usage Data"**
2. Clear browser cache
3. Run **"Scan Widget Usage Now"**
4. Test select all functionality

### **Fix 2: Enable Debug Logging**
Add this to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for messages like:
```
Elementor Editor Optimizer: Starting full widget usage scan
Elementor Editor Optimizer: Found 12 unique widgets in use
```

### **Fix 3: Check JavaScript Loading**
In browser console, type:
```javascript
console.log(typeof eeo_data);
console.log(jQuery);
```

Should show:
```
object
function
```

## 📋 **Verification Checklist**

Before reporting issues, verify:

- [ ] Elementor is installed and active
- [ ] At least one page built with Elementor exists  
- [ ] Plugin settings are saved with debug mode enabled
- [ ] Browser console shows no JavaScript errors
- [ ] "Scan Widget Usage Now" completes successfully
- [ ] Analytics dashboard shows used vs unused widgets
- [ ] "Select All" button checks all unused widget checkboxes
- [ ] Selected widgets transfer to main settings form
- [ ] Save settings disables the selected widgets

## 🎯 **Success Indicators**

✅ **Widget scan finds actual widgets from your pages**  
✅ **Analytics shows realistic used vs unused ratios**  
✅ **Bulk selection tools work smoothly**  
✅ **Backend editor loads faster after disabling unused widgets**  
✅ **No JavaScript errors in browser console**

If all checklist items pass, your widget usage analytics is working perfectly! 🚀