// ExoClass Calendar JavaScript
(function($) {
    'use strict';
    
    // Store original events for filtering
    let originalEvents = [];
    let calendar;
    
    // Store filter data from APIs
    let filterData = {
        locations: [],
        activities: [],
        difficultyLevels: [],
        ages: [],
        availableFilters: []
    };
    
    // API configuration from WordPress
    const API_CONFIG = exoclass_ajax.api_config;
    
    $(document).ready(function() {
        var calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) {
            return;
        }
        
        // Detect mobile device
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: isMobile() ? 'dayGridDay' : 'dayGridWeek',
            headerToolbar: {
                left: '',
                center: 'title',
                right: 'prev,next',
            },
            height: 'auto',
            contentHeight: 'auto',
            aspectRatio: null,
            locale: 'lt',
            firstDay: 1, // Monday start
            timeZone: 'Europe/Vilnius',
            dayMaxEvents: false,
            eventMaxStack: 3,
            
            // Events will be loaded from API
            events: function(fetchInfo, successCallback, failureCallback) {
                loadEventsFromAPI(successCallback, failureCallback, {});
            },
            
            // Custom event content rendering
                eventContent: function(arg) {
                    let event = arg.event;
                    let props = event.extendedProps;
                    
                    // Format time for Lithuanian locale
                    let startTime = event.start.toLocaleTimeString('lt-LT', { 
                        hour: '2-digit', 
                        minute: '2-digit', 
                        timeZone: 'Europe/Vilnius'
                    });
                    let endTime = event.end ? event.end.toLocaleTimeString('lt-LT', { 
                        hour: '2-digit', 
                        minute: '2-digit', 
                        timeZone: 'Europe/Vilnius'
                    }) : '';
                    let timeRange = startTime + (endTime ? ' - ' + endTime : '');
                    
                    // Determine availability status
                    let spotsClass = 'available';
                    let spotsText = 'Yra laisvų vietų';
                    let statusClass = '';
                    
                    if (props.availableSpots === 0) {
                        spotsClass = 'full';
                        spotsText = 'Nėra laisvų vietų';
                        statusClass = 'full';
                    } else if (props.availableSpots < 4) {
                        spotsClass = 'limited';
                        spotsText = `Liko ${props.availableSpots} vietos`;
                        statusClass = 'limited';
                    }
                    
                    // Get activity image or fallback to visual system
                    let { imageUrl, gradient, icon } = getActivityImage(props.activityData, props.activityName);
                    
                    // Use the original title as-is (user-set names must remain unchanged)
                    let displayTitle = event.title;
                    
                    // Create custom HTML content with image or fallback visual
                    let headerContent = '';
                    
                    // Check if images should be shown
                    if (exoclass_ajax.show_images) {
                        if (imageUrl) {
                            headerContent = `
                                <div class="event-image-header" style="background-image: url('${imageUrl}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                                    <div class="event-image-overlay" style="background: ${gradient}; opacity: 0.3;"></div>
                                </div>
                            `;
                        } else {
                            headerContent = `
                                <div class="event-image-header" style="background: ${gradient};">
                                    <div class="event-activity-icon">${icon}</div>
                                </div>
                            `;
                        }
                    }
                    
                    let html = `
                        <div class="event-content ${!exoclass_ajax.show_images ? 'compact' : ''}" data-group-id="${props.groupId || ''}">
                            ${headerContent}
                            <div class="event-main-content">
                                <div class="event-time">${timeRange}</div>
                                <div class="event-title" title="${event.title}">${displayTitle}</div>
                                <div class="event-teacher">${props.teacher || 'Treneris'}</div>
                                <div class="event-spots ${spotsClass}">
                                    <span class="spots-icon">👥</span>
                                    ${spotsText}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    return { html: html };
                },
            
            // Event interaction - show modal with event details
            eventClick: function(info) {
                const event = info.event;
                const props = event.extendedProps;
                const $modal = $('#eventDetailsModal');
                
                if (!$modal.length) {
                    console.error('Modal element not found');
                    return;
                }
                
                // Format time for Lithuanian locale
                const startTime = event.start.toLocaleTimeString('lt-LT', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'Europe/Vilnius'
                });
                const endTime = event.end ? event.end.toLocaleTimeString('lt-LT', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'Europe/Vilnius'
                }) : '';
                const timeRange = startTime + (endTime ? ' - ' + endTime : '');
                
                // Get activity image or fallback
                const { imageUrl, gradient } = getActivityImage(props.activityData, props.activityName);
                
                // Update modal content
                $modal.find('.event-modal-image').css({
                    'background-image': imageUrl ? `url('${imageUrl}')` : 'none',
                    'background': imageUrl ? `url('${imageUrl}') center/cover` : gradient
                });
                
                $modal.find('.event-modal-title').text(event.title);
                $modal.find('.event-time').text(timeRange);
                $modal.find('.event-teacher').text(props.teacher || 'Treneris');
                
                // Format location as single line: "SKILLZ centras - Lukiškių g. 5, 01108 Vilnius"
                let locationText = 'Nenurodyta';
                if (props.locationData) {
                    locationText = props.locationData.name || 'Nenurodyta';
                    if (props.locationData.address) {
                        // Format as: Name - Full Address
                        locationText += ` - ${props.locationData.address}`;
                    }
                }
                $modal.find('.event-location').text(locationText);
                
                // Update spots information
                let spotsText;
                if (props.availableSpots === 0) {
                    spotsText = 'Nėra laisvų vietų';
                } else if (props.availableSpots < 4) {
                    spotsText = `Liko ${props.availableSpots} vietos`;
                } else {
                    spotsText = 'Yra laisvų vietų';
                }
                $modal.find('.event-spots').text(spotsText);
                
                // Update difficulty level
                $modal.find('.event-difficulty').text(props.difficulty || 'Nenurodytas');
                
                // Update description if available
                const $descriptionEl = $modal.find('.event-modal-description');
                const $descriptionContent = $modal.find('.description-content');
                const $descriptionReadMore = $modal.find('.description-read-more');
                
                if (props.activityData && props.activityData.description) {
                    $descriptionContent.html(props.activityData.description);
                    $descriptionEl.show();
                    
                    // Calculate dynamic max height based on iframe
                    setTimeout(() => {
                        const $iframe = $descriptionContent.find('iframe');
                        const iframeHeight = $iframe.outerHeight() || 0;
                        const textHeight = $descriptionContent.find('p, div:not(:has(iframe))').outerHeight() || 0;
                        
                        // Calculate dynamic max height: iframe height + text height + some padding
                        let dynamicMaxHeight = iframeHeight + textHeight + 100; // 50px padding
                        
                        // Minimum height of 200px, maximum of 400px
                        dynamicMaxHeight = Math.max(250, Math.min(550, dynamicMaxHeight));
                        
                        // Set the dynamic max height via CSS
                        $descriptionContent.css('max-height', dynamicMaxHeight + 'px');
                        
                        // Check if we need read more button
                        const contentHeight = $descriptionContent[0].scrollHeight;
                        
                        if (contentHeight > dynamicMaxHeight) {
                            $descriptionReadMore.show();
                        } else {
                            $descriptionReadMore.hide();
                        }
                    }, 200); // Longer delay to ensure iframe is fully loaded
                } else {
                    $descriptionEl.hide();
                }
                
                // Update teacher information if available
                const $teacherInfo = $modal.find('.event-modal-teacher-info');
                const $teacherName = $modal.find('.teacher-name');
                const $teacherPhoto = $modal.find('.teacher-photo');
                const $teacherPhotoPlaceholder = $modal.find('.teacher-photo-placeholder');
                const $teacherDescriptionContent = $modal.find('.teacher-description-content');
                const $readMoreBtn = $teacherInfo.find('.read-more-btn');
                
                if (props.teacherData && props.teacher) {
                    // Set teacher name
                    $teacherName.text(props.teacher);
                    
                    // Handle teacher photo - check multiple possible sources
                    let teacherPhotoUrl = null;
                    
                    // Method 1: Employee provider medias (actual API structure)
                    if (props.teacherData.employee_provider && 
                        props.teacherData.employee_provider.medias && 
                        Array.isArray(props.teacherData.employee_provider.medias) && 
                        props.teacherData.employee_provider.medias.length > 0) {
                        teacherPhotoUrl = props.teacherData.employee_provider.medias[0].full_path;
                    }
                    // Method 2: Direct photo field
                    else if (props.teacherData.photo) {
                        teacherPhotoUrl = props.teacherData.photo;
                    }
                    // Method 3: Profile image field
                    else if (props.teacherData.profile_image) {
                        teacherPhotoUrl = props.teacherData.profile_image;
                    }
                    // Method 4: Image field
                    else if (props.teacherData.image) {
                        teacherPhotoUrl = props.teacherData.image;
                    }
                    // Method 5: Avatar field
                    else if (props.teacherData.avatar) {
                        teacherPhotoUrl = props.teacherData.avatar;
                    }
                    // Method 6: Employee provider photo (legacy)
                    else if (props.teacherData.employee_provider && props.teacherData.employee_provider.photo) {
                        teacherPhotoUrl = props.teacherData.employee_provider.photo;
                    }
                    // Method 7: Employee provider profile_image (legacy)
                    else if (props.teacherData.employee_provider && props.teacherData.employee_provider.profile_image) {
                        teacherPhotoUrl = props.teacherData.employee_provider.profile_image;
                    }
                    
                    // Debug logging for photo URL
                    if (props.teacherData && console && console.log) {
                        console.log('Teacher data for photo lookup:', props.teacherData);
                        console.log('Found photo URL:', teacherPhotoUrl);
                    }
                    
                    if (teacherPhotoUrl) {
                        $teacherPhoto.attr('src', teacherPhotoUrl).show();
                        $teacherPhotoPlaceholder.hide();
                    } else {
                        $teacherPhoto.hide();
                        $teacherPhotoPlaceholder.show();
                    }
                    
                    // Create teacher description from available data
                    let teacherDescription = '';
                    
                    // Check for employee_provider description (rich teacher bio)
                    if (props.teacherData.employee_provider && props.teacherData.employee_provider.description) {
                        teacherDescription = props.teacherData.employee_provider.description;
                    } else if (props.teacherData.bio) {
                        teacherDescription = props.teacherData.bio;
                    } else if (props.teacherData.description) {
                        teacherDescription = props.teacherData.description;
                    } else {
                        // Create a basic description from available info
                        teacherDescription = `${props.teacher} yra patyręs ir kvalifikuotas treneris, specializuojantis šioje veikloje.`;
                    }
                    
                    if (teacherDescription) {
                        // Set the full description
                        $teacherDescriptionContent.html(teacherDescription);
                        
                        // Show/hide read more button based on content length
                        if (teacherDescription.length > 200) {
                            $readMoreBtn.show();
                        } else {
                            $readMoreBtn.hide();
                        }
                        
                        $teacherInfo.show();
                    } else {
                        $teacherInfo.hide();
                    }
                } else {
                    $teacherInfo.hide();
                }
                
                // Update registration button
                const $registerButton = $modal.find('.register-button');
                if (props.groupExternalKey || props.groupId) {
                    const groupKey = props.groupExternalKey || props.groupId;
                    const groupManagementUrl = `${API_CONFIG.embed_url}/lt/embed/provider/${API_CONFIG.provider_key}/group-management/${groupKey}`;
                    $registerButton.attr('href', groupManagementUrl);
                    $registerButton.show();
                } else {
                    $registerButton.hide();
                }
                
                // Show modal with animation
                $modal.addClass('show');
                $modal.find('.event-modal-content').addClass('modal-animate-in');
                
                // Prevent body scrolling when modal is open
                $('body').css('overflow', 'hidden');
            },
            
            // Customize day headers
            dayHeaderContent: function(arg) {
                return arg.text;
            },
            
            // Enable day clicking
            dateClick: function(info) {
                // You could add functionality to create new events here
            },
            
            // Responsive design
            windowResize: function() {
                calendar.updateSize();
                
                // Switch view based on screen size
                const currentView = calendar.view.type;
                const isMobileNow = window.innerWidth <= 768;
                
                if (isMobileNow && currentView === 'dayGridWeek') {
                    // Switch to day view on mobile
                    calendar.changeView('dayGridDay');
                } else if (!isMobileNow && currentView === 'dayGridDay') {
                    // Switch to week view on desktop
                    calendar.changeView('dayGridWeek');
                }
            }
        });
        
        calendar.render();
        
        // Force calendar to resize to content after render
        setTimeout(() => {
            calendar.updateSize();
            forceCalendarHeight();
        }, 100);
        
        // Load filter data and initialize filters
        loadFilterData().then(() => {
            initializeFilters();
            applyInitialFilters(); // Apply initial filters from shortcode
            setupEventHoverEffects();
            // Force resize again after data loads
            setTimeout(() => {
                forceCalendarHeight();
            }, 200);
        });

        // Modal close handlers
        // Using event delegation for the close button
        $(document).on('click', '#eventDetailsModal .close-modal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
        });
        
        // Click outside modal handler
        $(document).on('click', '#eventDetailsModal', function(e) {
            if ($(e.target).is('#eventDetailsModal')) {
                closeModal();
            }
        });
        
        // Close modal with escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#eventDetailsModal').hasClass('show')) {
                closeModal();
            }
        });
        
        function closeModal() {
            const $modal = $('#eventDetailsModal');
            $modal.removeClass('show');
            $('body').css('overflow', '');
            $modal.find('.event-modal-content').removeClass('modal-animate-in');
        }
    });
    

    
    // Setup hover effects for related events
    function setupEventHoverEffects() {
        // Use event delegation for hover effects
        $('#calendar').on('mouseenter', '.fc-event', function() {
            const eventContent = $(this).find('.event-content');
            if (eventContent.length) {
                const groupId = eventContent.attr('data-group-id');
                if (groupId) {
                    highlightRelatedEvents(groupId);
                }
            }
        });
        
        $('#calendar').on('mouseleave', '.fc-event', function() {
            clearEventHighlights();
        });
    }
    
    // Highlight all events from the same group
    function highlightRelatedEvents(groupId) {
        $('.fc-event').each(function() {
            const eventContent = $(this).find('.event-content');
            if (eventContent.length && eventContent.attr('data-group-id') === groupId) {
                $(this).addClass('highlight-related');
            }
        });
    }
    
    // Clear all event highlights
    function clearEventHighlights() {
        $('.fc-event.highlight-related').removeClass('highlight-related');
    }
    
    // Smooth loading indicator functions
    function showLoadingIndicator() {
        const loadingIndicator = $('#loadingIndicator');
        const calendarContainer = $('.calendar-container');
        
        loadingIndicator.css('display', 'flex');
        calendarContainer.addClass('loading');
        
        setTimeout(() => {
            loadingIndicator.addClass('show');
        }, 10);
    }
    
    function hideLoadingIndicator() {
        const loadingIndicator = $('#loadingIndicator');
        const calendarContainer = $('.calendar-container');
        
        loadingIndicator.removeClass('show');
        calendarContainer.removeClass('loading');
        
        setTimeout(() => {
            loadingIndicator.css('display', 'none');
        }, 400); // Match the CSS transition duration
    }
    
    // Load all filter data from APIs using WordPress AJAX
    async function loadFilterData() {
        showLoadingIndicator();
        
        try {
            const response = await $.ajax({
                url: exoclass_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'exoclass_get_filters',
                    nonce: exoclass_ajax.nonce
                }
            });
            
            if (response.success) {
                filterData = response.data;
            }
            
        } catch (error) {
            // Error loading filter data
        } finally {
            hideLoadingIndicator();
        }
    }
    
    // Load events from ExoClass API using WordPress AJAX
    async function loadEventsFromAPI(successCallback, failureCallback, filters = {}) {
        try {
            // Show loading indicator
            showLoadingIndicator();
            
            const response = await $.ajax({
                url: exoclass_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'exoclass_get_events',
                    nonce: exoclass_ajax.nonce,
                    filters: filters
                }
            });
            
            if (response.success) {
                const events = response.data;
                
                // Store original events for filtering
                originalEvents = events.map(event => ({
                    title: event.title,
                    start: new Date(event.start),
                    end: new Date(event.end),
                    className: event.className,
                    extendedProps: event.extendedProps
                }));
                
                // Hide loading indicator
                hideLoadingIndicator();
                
                if (events.length === 0) {
                    const fallbackEvents = getFallbackEvents();
                    originalEvents = fallbackEvents;
                    successCallback(fallbackEvents);
                } else {
                    successCallback(events);
                }
            } else {
                throw new Error(response.data.message || 'Unknown error');
            }
            
        } catch (error) {
            // Error loading events from API
            
            // Hide loading indicator
            hideLoadingIndicator();
            
            // Show error message and fallback to sample data
            const errorDiv = $('<div>').css({
                'background': '#fff3cd',
                'border': '1px solid #ffeaa7',
                'color': '#856404',
                'padding': '10px',
                'margin': '10px 0',
                'border-radius': '4px',
                'text-align': 'center'
            }).html(`
                ⚠️ Could not load live data from API. Showing sample data instead.<br>
                <small>Error: ${error.message}</small>
            `);
            
            $('.exoclass-calendar-container').prepend(errorDiv);
            
            const fallbackEvents = getFallbackEvents();
            originalEvents = fallbackEvents;
            successCallback(fallbackEvents);
        }
    }
    
    // Determine class category based on activity name
    function getClassCategory(activityName) {
        const name = activityName.toLowerCase();
        
        if (name.includes('yoga') || name.includes('pilates') || name.includes('meditation')) {
            return 'yoga-class';
        } else if (name.includes('dance') || name.includes('zumba') || name.includes('rhythm')) {
            return 'dance-class';
        } else if (name.includes('strength') || name.includes('weight') || name.includes('muscle') || name.includes('sculpt')) {
            return 'strength-class';
        } else if (name.includes('cardio') || name.includes('hiit') || name.includes('running') || name.includes('cycle')) {
            return 'cardio-class';
        } else {
            return 'fitness-class';
        }
    }
    
    // Fallback events if API fails
    function getFallbackEvents() {
        return [
            {
                title: 'POWER COMBO TSH',
                start: getDateString(0, 7, 30),
                end: getDateString(0, 8, 20),
                className: 'strength-class',
                extendedProps: {
                    teacher: 'Sarah Johnson',
                    availableSpots: 5,
                    maxSpots: 15
                }
            },
            {
                title: 'ATHLETICS TSH', 
                start: getDateString(0, 8, 0),
                end: getDateString(0, 9, 0),
                className: 'fitness-class',
                extendedProps: {
                    teacher: 'Mike Davis',
                    availableSpots: 0,
                    maxSpots: 12
                }
            }
        ];
    }
    
    // Initialize filter functionality
    function initializeFilters() {
        // Populate filter dropdowns from API data
        populateLocationDropdown();
        populateActivityDropdown(); 
        populateDifficultyDropdown();
        populateAgeDropdown();
        populateTeacherDropdown(); // Add this line
        
        // Add change listeners to all filter dropdowns
        const dropdowns = ['#locationDropdown', '#activityDropdown', '#difficultyDropdown', '#ageDropdown', '#availabilityDropdown', '#teacherDropdown']; // Add teacherDropdown
        dropdowns.forEach(selector => {
            $(selector).on('change', function() {
                // Auto-apply filters when dropdown changes
                applyFiltersToCalendar();
            });
        });
        
        // Clear filters
        $('#clearFilters').on('click', function() {
            // Reset all dropdown values
            $('#locationDropdown').val('');
            $('#activityDropdown').val('');
            $('#difficultyDropdown').val('');
            $('#ageDropdown').val('');
            $('#availabilityDropdown').val('');
            $('#teacherDropdown').val(''); // Add this line
            
            // Remove existing event sources and reload without filters
            calendar.removeAllEventSources();
            calendar.addEventSource(function(fetchInfo, successCallback, failureCallback) {
                loadEventsFromAPI(successCallback, failureCallback, {});
            });
        });
        
        // Apply filters
        $('#applyFilters').on('click', function() {
            applyFiltersToCalendar();
        });
    }
    
    // Populate location dropdown
    function populateLocationDropdown() {
        const locationDropdown = $('#locationDropdown');
        
        if (filterData.locations && filterData.locations.length > 0) {
            filterData.locations.forEach(location => {
                // Create shorter address: street + city
                let displayText = location.name;
                
                if (location.address) {
                    // Remove postal code and country from address, but keep city
                    const addressParts = location.address.split(',');
                    // Keep all parts except the last one (country), and remove postal code
                    const streetCityParts = addressParts.slice(0, -1); // Remove country
                    const streetAndCity = streetCityParts.join(',').trim();
                    displayText = streetAndCity || location.name;
                }
                
                locationDropdown.append(`<option value="${location.id}">${displayText}</option>`);
            });
        }
    }
    
    // Populate activity dropdown
    function populateActivityDropdown() {
        const activityDropdown = $('#activityDropdown');
        
        if (filterData.activities && filterData.activities.length > 0) {
            filterData.activities.forEach(activity => {
                activityDropdown.append(`<option value="${activity.id}">${activity.name}</option>`);
            });
        }
    }
    
    // Populate difficulty dropdown
    function populateDifficultyDropdown() {
        const difficultyDropdown = $('#difficultyDropdown');
        const difficultyGroup = $('#difficultyGroup');
        
        if (filterData.difficulty_levels && filterData.difficulty_levels.length > 0) {
            difficultyGroup.show();
            
            filterData.difficulty_levels.forEach(difficulty => {
                difficultyDropdown.append(`<option value="${difficulty.id}">${difficulty.name}</option>`);
            });
        } else {
            difficultyGroup.hide();
        }
    }
    
    // Populate age dropdown
    function populateAgeDropdown() {
        const ageDropdown = $('#ageDropdown');
        const ageGroup = $('#ageGroup');
        
        if (filterData.filters && filterData.filters.age_filter && filterData.filters.age_filter.ages) {
            ageGroup.show();
            
            filterData.filters.age_filter.ages.forEach(age => {
                ageDropdown.append(`<option value="${age.id}">${age.name || age.value}</option>`);
            });
        } else {
            ageGroup.hide();
        }
    }

    // Populate teacher dropdown
    function populateTeacherDropdown() {
        const teacherDropdown = $('#teacherDropdown');
        const teacherGroup = $('#teacherGroup');
        
        if (filterData.teachers && filterData.teachers.length > 0) {
            teacherGroup.show();
            
            filterData.teachers.forEach(teacher => {
                const teacherName = teacher.first_name + (teacher.last_name ? ' ' + teacher.last_name : '');
                teacherDropdown.append(`<option value="${teacher.id}">${teacherName}</option>`);
            });
        } else {
            teacherGroup.hide();
        }
    }
    
    // Apply filters to calendar
    function applyFiltersToCalendar() {
        // Collect filter values from dropdowns
        const filters = {};
        
        const locationValue = $('#locationDropdown').val();
        if (locationValue) filters.locations = [locationValue];
        
        const activityValue = $('#activityDropdown').val();
        if (activityValue) filters.activities = [activityValue];
        
        const difficultyValue = $('#difficultyDropdown').val();
        if (difficultyValue) filters.difficulties = [difficultyValue];
        
        const ageValue = $('#ageDropdown').val();
        if (ageValue) filters.ages = [ageValue];
        
        const teacherValue = $('#teacherDropdown').val();
        if (teacherValue) filters.teachers = [teacherValue];
        
        // Get availability filter
        const availabilityValue = $('#availabilityDropdown').val();
        
        // Remove existing event sources to prevent duplicates
        calendar.removeAllEventSources();
        
        // Create new event source with filters
        calendar.addEventSource(function(fetchInfo, successCallback, failureCallback) {
            loadEventsFromAPI(function(events) {
                // Apply client-side availability filter if needed
                let filteredEvents = events;
                
                if (availabilityValue) {
                    filteredEvents = events.filter(event => {
                        const availableSpots = event.extendedProps?.availableSpots || 0;
                        if (availabilityValue === 'available' && availableSpots === 0) return false;
                        if (availabilityValue === 'full' && availableSpots > 0) return false;
                        return true;
                    });
                }
                
                successCallback(filteredEvents);
            }, function(error) {
                failureCallback(error);
            }, filters);
        });
    }
    
    // Force calendar height to fit content
    function forceCalendarHeight() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        
        // Remove any inline height styles
        calendarEl.style.height = 'auto';
        calendarEl.style.minHeight = 'auto';
        
        // Force all FullCalendar elements to auto height
        const fcElements = calendarEl.querySelectorAll('.fc-view-harness, .fc-scrollgrid, .fc-scrollgrid-sync-table, .fc-daygrid, .fc-daygrid-body, .fc-daygrid-day, .fc-daygrid-day-frame');
        fcElements.forEach(el => {
            el.style.height = 'auto';
            el.style.minHeight = 'auto';
        });
        
        // Update calendar size
        if (calendar) {
            calendar.updateSize();
        }
    }
    
    // Get activity image from API data or fallback to visual system
    function getActivityImage(activityData, activityName) {
        let imageUrl = null;
        
        // Try to get real image from activity data
        if (activityData) {
            // First, try to get from main_media_path
            if (activityData.main_media_path) {
                // Construct full image URL
                const baseDomain = exoclass_ajax.api_config.base_url.replace('/api/v1/en', '');
                imageUrl = `${baseDomain}/${activityData.main_media_path}`;
            }
            // If no main_media_path, try images array
            else if (activityData.images && Array.isArray(activityData.images) && activityData.images.length > 0) {
                const firstImage = activityData.images[0];
                if (firstImage.path) {
                    const baseDomain = exoclass_ajax.api_config.base_url.replace('/api/v1/en', '');
                    imageUrl = `${baseDomain}/${firstImage.path}`;
                } else if (firstImage.url) {
                    imageUrl = firstImage.url;
                }
            }
        }
        
        // Get fallback visuals for gradient and icon
        const fallbackVisuals = getActivityVisuals(activityName);
        
        return {
            imageUrl: imageUrl,
            gradient: fallbackVisuals.gradient,
            icon: fallbackVisuals.icon
        };
    }
    
    // Get activity visuals (icon and gradient) based on activity name
    function getActivityVisuals(activityName) {
        if (!activityName) {
            return {
                icon: '💪',
                gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
            };
        }
        
        const name = activityName.toLowerCase();
        
        // Hip-Hop activities
        if (name.includes('hip-hop') || name.includes('hip hop')) {
            return {
                icon: '🎤',
                gradient: 'linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%)'
            };
        }
        
        // House activities  
        if (name.includes('house')) {
            return {
                icon: '🏠',
                gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
            };
        }
        
        // Choreography activities
        if (name.includes('choreo')) {
            return {
                icon: '💃',
                gradient: 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)'
            };
        }
        
        // Yoga/Pilates activities
        if (name.includes('yoga') || name.includes('pilates')) {
            return {
                icon: '🧘',
                gradient: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
            };
        }
        
        // Dance activities
        if (name.includes('dance') || name.includes('zumba')) {
            return {
                icon: '💃',
                gradient: 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)'
            };
        }
        
        // Strength/Weight activities
        if (name.includes('strength') || name.includes('weight') || name.includes('muscle') || name.includes('sculpt')) {
            return {
                icon: '🏋️',
                gradient: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)'
            };
        }
        
        // Cardio activities
        if (name.includes('cardio') || name.includes('hiit') || name.includes('running') || name.includes('cycle')) {
            return {
                icon: '🔥',
                gradient: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
            };
        }
        
        // Default fitness
        return {
            icon: '💪',
            gradient: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'
        };
    }
    
    // Helper function to generate dates relative to today
    function getDateString(daysFromToday, hour = null, minute = null) {
        var date = new Date();
        date.setDate(date.getDate() + daysFromToday);
        
        if (hour !== null) {
            date.setHours(hour, minute || 0, 0, 0);
            return date.toISOString();
        } else {
            // Return date only (all day event)
            return date.toISOString().split('T')[0];
        }
    }
    
    // Modal close handlers
    const $modal = $('#eventDetailsModal');
    const $closeBtn = $modal.find('.close-modal');
    
    // Close button click handler
    $closeBtn.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeModal();
    });
    
    // Click outside modal handler
    $modal.on('click', function(e) {
        if ($(e.target).is($modal)) {
            closeModal();
        }
    });
    
    // Close modal with escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.hasClass('show')) {
            closeModal();
        }
    });
    
    function closeModal() {
        $modal.removeClass('show');
        $('body').css('overflow', '');
        $modal.find('.event-modal-content').removeClass('modal-animate-in');
    }
    
    // Apply initial filters from shortcode attributes
    function applyInitialFilters() {
        if (exoclass_ajax.initial_filters) {
            const filters = exoclass_ajax.initial_filters;
            
            // Set dropdown values based on initial filters
            if (filters.location) {
                $('#locationDropdown').val(filters.location);
            }
            if (filters.activity) {
                $('#activityDropdown').val(filters.activity);
            }
            if (filters.teacher) {
                $('#teacherDropdown').val(filters.teacher);
            }
            if (filters.level) {
                $('#difficultyDropdown').val(filters.level);
            }
            if (filters.availability) {
                $('#availabilityDropdown').val(filters.availability);
            }
            
            // Apply the filters if any are set
            if (filters.location || filters.activity || filters.teacher || filters.level || filters.availability) {
                applyFiltersToCalendar();
            }
        }
    }
    
    // Simple read more functionality for teacher description
    $(document).on('click', '.read-more-btn', function() {
        const $btn = $(this);
        
        // Check if it's for teacher description or event description
        if ($btn.hasClass('description-read-more')) {
            // Handle event description
            const $desc = $btn.siblings('.description-content');
            
            if ($desc.hasClass('expanded')) {
                $desc.removeClass('expanded');
                $btn.text('Skaityti daugiau');
            } else {
                $desc.addClass('expanded');
                $btn.text('Rodyti mažiau');
            }
        } else {
            // Handle teacher description
            const $desc = $btn.siblings('.teacher-description-content');
            
            if ($desc.hasClass('expanded')) {
                $desc.removeClass('expanded');
                $btn.text('Skaityti daugiau');
            } else {
                $desc.addClass('expanded');
                $btn.text('Rodyti mažiau');
            }
        }
    });
    
})(jQuery); 