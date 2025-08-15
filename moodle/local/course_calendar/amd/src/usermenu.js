/**
 * Add custom link to user menu
 */
define(['jquery', 'core/str'], function($, str) {
    
    /**
     * Initialize the user menu modification
     */
    var init = function() {
        
        // Wait for DOM to be ready
        $(document).ready(function() {
            
            // Look for user menu
            var userMenu = $('[data-region="user-menu"] .dropdown-menu, .usermenu .dropdown-menu');
            
            if (userMenu.length > 0) {
                
                // Get the string for the link text
                str.get_string('my_absence_requests', 'local_course_calendar').done(function(linkText) {
                    
                    // Create the new menu item
                    var newMenuItem = $('<a>').attr({
                        'href': M.cfg.wwwroot + '/local/course_calendar/pages/my_absence_requests.php',
                        'class': 'dropdown-item'
                    }).text(linkText);
                    
                    var newLi = $('<li>').append(newMenuItem);
                    
                    // Find logout link to insert before it
                    var logoutLink = userMenu.find('a[href*="login/logout.php"]');
                    if (logoutLink.length > 0) {
                        logoutLink.parent().before(newLi);
                    } else {
                        // If no logout link found, append to end
                        userMenu.append(newLi);
                    }
                    
                    console.log('Custom user menu item added');
                    
                }).fail(function() {
                    // Fallback if string loading fails
                    var newMenuItem = $('<a>').attr({
                        'href': M.cfg.wwwroot + '/local/course_calendar/pages/my_absence_requests.php',
                        'class': 'dropdown-item'
                    }).text('My Absence Requests');
                    
                    var newLi = $('<li>').append(newMenuItem);
                    userMenu.append(newLi);
                });
                
            } else {
                console.log('User menu not found');
            }
        });
    };
    
    return {
        init: init
    };
});
