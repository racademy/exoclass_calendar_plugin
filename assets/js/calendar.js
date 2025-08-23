// ExoClass Calendar JavaScript
(function($) {
    'use strict';
    
    // Store original events for filtering
    let originalEvents = [];
    let calendar;
    let isInitialLoad = true; // Track if this is the first load
    
    // Store filter data from APIs
    let filterData = {
        locations: [],
        activities: [],
        difficultyLevels: [],
        ages: [],
        classes: [],
        teachers: [],
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
                    
                    // Check if current week is empty and navigate to first week with events
                    if (isInitialLoad) {
                        setTimeout(() => {
                            checkAndNavigateToFirstWeekWithEvents(events);
                            isInitialLoad = false; // Ensure this only runs once
                        }, 100); // Small delay to ensure calendar is fully rendered
                    }
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
        populateClassDropdown();
        populateTeacherDropdown();
        

        
        // Initialize Select2 for all multi-select dropdowns
        initializeSelect2();
        
        // Force width adjustment after all dropdowns are populated
        setTimeout(() => {
            $('.select2-container').each(function() {
                $(this).css({
                    'width': 'auto',
                    'min-width': '120px',
                    'max-width': '250px',
                    'display': 'inline-block'
                });
            });
        }, 200);
        

        
        // Add change listeners to Select2 filter dropdowns (excluding custom button selectors)
        const dropdowns = ['#locationDropdown', '#activityDropdown', '#difficultyDropdown', '#teacherDropdown'];
        dropdowns.forEach(selector => {
            $(selector).on('change', function() {
                // Auto-apply filters when dropdown changes
                applyFiltersToCalendar();
            });
        });
        
        // Clear filters
        $('#clearFilters').on('click', function() {
            // Reset all dropdown values and trigger Select2 update
            $('#locationDropdown').val(null).trigger('change');
            $('#activityDropdown').val(null).trigger('change');
            $('#difficultyDropdown').val(null).trigger('change');
            $('#teacherDropdown').val(null).trigger('change');
            
            // Clear custom button selectors
            $('#ageButtonSelector .age-button').removeClass('selected');
            $('#classButtonSelector .class-button').removeClass('selected');
            
            // Update dropdown texts
            updateDropdownText('#ageCustomDropdown', 'Visi amžiai');
            updateDropdownText('#classCustomDropdown', 'Visos klasės');
            
            // Remove existing event sources and reload without filters
            calendar.removeAllEventSources();
            calendar.addEventSource(function(fetchInfo, successCallback, failureCallback) {
                loadEventsFromAPI(function(events) {
                    successCallback(events);
                    
                    // Check if current week is empty after clearing filters and navigate if needed
                    setTimeout(() => {
                        checkAndNavigateToFirstWeekWithEvents(events);
                    }, 100);
                }, failureCallback, {});
            });
        });
        
        // Apply filters
        $('#applyFilters').on('click', function() {
            applyFiltersToCalendar();
        });
        
        // Close custom dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.custom-dropdown').length) {
                $('.custom-dropdown').removeClass('open');
            }
        });
    }
    
    // Toggle custom dropdown open/close
    function toggleCustomDropdown(dropdownSelector) {
        const $dropdown = $(dropdownSelector);
        const isOpen = $dropdown.hasClass('open');
        
        // Close all other custom dropdowns
        $('.custom-dropdown').removeClass('open');
        
        // Toggle this dropdown
        if (!isOpen) {
            $dropdown.addClass('open');
        }
    }
    
    // Update dropdown text based on selected items
    function updateDropdownText(dropdownSelector, defaultText) {
        const $dropdown = $(dropdownSelector);
        const $text = $dropdown.find('.dropdown-text');
        const selectedButtons = $dropdown.find('.age-button.selected, .class-button.selected');
        
        if (selectedButtons.length === 0) {
            $text.text(defaultText);
        } else if (selectedButtons.length === 1) {
            $text.text(selectedButtons.first().text());
        } else {
            $text.text(`${selectedButtons.length} pasirinkta`);
        }
    }
    
    // Initialize Select2 for all multi-select dropdowns (excluding hidden inputs)
    function initializeSelect2() {
        $('.filter-dropdown[multiple]').not('input[type="hidden"]').each(function() {
            const $this = $(this);
            const placeholder = $this.find('option:first').text();
            
            $this.select2({
                placeholder: placeholder,
                allowClear: true,
                width: 'style',
                dropdownAutoWidth: true,
                language: {
                    noResults: function() {
                        return 'Nerasta rezultatų';
                    },
                    searching: function() {
                        return 'Ieškoma...';
                    }
                }
            });
            
            // Force width adjustment after initialization and on any changes
            const adjustWidth = function() {
                const $container = $this.next('.select2-container');
                $container.css({
                    'width': 'auto',
                    'min-width': '120px',
                    'max-width': '250px',
                    'display': 'inline-block'
                });
            };
            
            // Apply width immediately
            adjustWidth();
            
            // Reapply width on various Select2 events
            $this.on('select2:select select2:unselect select2:clear', adjustWidth);
            
            // Also apply after a short delay to catch any async updates
            setTimeout(adjustWidth, 100);
            
            // Add scroll indicator when dropdown opens
            $this.on('select2:open', function() {
                setTimeout(() => {
                    const $results = $('.select2-results__options');
                    if ($results.length) {
                        const scrollHeight = $results[0].scrollHeight;
                        const clientHeight = $results[0].clientHeight;
                        
                        if (scrollHeight > clientHeight) {
                            $results.attr('data-has-scroll', 'true');
                        } else {
                            $results.removeAttr('data-has-scroll');
                        }
                    }
                }, 50);
            });
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
    
    // Populate age dropdown with custom button selector
    function populateAgeDropdown() {
        const ageGroup = $('#ageGroup');
        const ageButtonSelector = $('#ageButtonSelector');
        
        // Always show age group since we have hardcoded data
        ageGroup.show();
        
        // Check multiple possible sources for age data
        let ageData = null;
        
        // Method 1: Check filters.age_filter.ages (primary structure)
        if (filterData.filters && filterData.filters.age_filter && filterData.filters.age_filter.ages) {
            ageData = filterData.filters.age_filter.ages;
        }
        // Method 2: Check if ages is directly in filterData
        else if (filterData.ages && Array.isArray(filterData.ages)) {
            ageData = filterData.ages;
        }
        // Method 3: Use hardcoded age data if no API data
        else {
            ageData = [
                {id: -1, name: '<6 mėn'},
                {id: 0, name: '6-12 mėn'},
                {id: 1, name: '1 metų'},
                {id: 2, name: '2 metų'},
                {id: 3, name: '3 metų'},
                {id: 4, name: '4 metų'},
                {id: 5, name: '5 metų'},
                {id: 6, name: '6 metų'},
                {id: 7, name: '7 metų'},
                {id: 8, name: '8 metų'},
                {id: 9, name: '9 metų'},
                {id: 10, name: '10 metų'},
                {id: 11, name: '11 metų'},
                {id: 12, name: '12 metų'},
                {id: 13, name: '13 metų'},
                {id: 14, name: '14 metų'},
                {id: 15, name: '15 metų'},
                {id: 16, name: '16 metų'},
                {id: 17, name: '17 metų'},
                {id: 18, name: 'Suaugusieji'}
            ];
        }
        
        if (ageData && ageData.length > 0) {
            // Clear existing buttons
            ageButtonSelector.empty();
            
            // Create buttons for each age group
            ageData.forEach(age => {
                const displayName = age.name || age.value || age.display_name || `Amžius ${age.id}`;
                const button = $(`<button class="age-button" data-value="${age.id}">${displayName}</button>`);
                ageButtonSelector.append(button);
            });
            
            // Add click handlers for age buttons
            ageButtonSelector.on('click', '.age-button', function(e) {
                e.preventDefault();
                $(this).toggleClass('selected');
                updateDropdownText('#ageCustomDropdown', 'Visi amžiai');
                applyFiltersToCalendar();
            });
            
            // Add clear button handler
            $('#clearAgeSelection').on('click', function(e) {
                e.preventDefault();
                ageButtonSelector.find('.age-button').removeClass('selected');
                updateDropdownText('#ageCustomDropdown', 'Visi amžiai');
                applyFiltersToCalendar();
            });
            
            // Add dropdown toggle handler
            $('#ageCustomDropdown .custom-dropdown-trigger').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleCustomDropdown('#ageCustomDropdown');
            });
            

        }
    }
    
    // Populate class dropdown with custom button selector
    function populateClassDropdown() {
        const classGroup = $('#classGroup');
        const classButtonSelector = $('#classButtonSelector');
        
        // Always show class group since we have hardcoded data
        classGroup.show();
        
        // Check if we have classes data from API, otherwise use hardcoded
        let classData = null;
        
        if (filterData.classes && filterData.classes.length > 0) {
            classData = filterData.classes;
        } else {
            // Use hardcoded class data
            classData = [
                {id: -1, name: 'Darželinukai'},
                {id: 0, name: 'Nulinukai'},
                {id: 1, name: '1 Klasės'},
                {id: 2, name: '2 Klasės'},
                {id: 3, name: '3 Klasės'},
                {id: 4, name: '4 Klasės'},
                {id: 5, name: '5 Klasės'},
                {id: 6, name: '6 Klasės'},
                {id: 7, name: '7 Klasės'},
                {id: 8, name: '8 Klasės'},
                {id: 9, name: '9 Klasės'},
                {id: 10, name: '10 Klasės'},
                {id: 11, name: '11 Klasės'},
                {id: 12, name: '12 Klasės'}
            ];
        }
        
        if (classData && classData.length > 0) {
            // Clear existing buttons
            classButtonSelector.empty();
            
            // Create buttons for each class
            classData.forEach(classItem => {
                const button = $(`<button class="class-button" data-value="${classItem.id}">${classItem.name}</button>`);
                classButtonSelector.append(button);
            });
            
            // Add click handlers for class buttons
            classButtonSelector.on('click', '.class-button', function(e) {
                e.preventDefault();
                $(this).toggleClass('selected');
                updateDropdownText('#classCustomDropdown', 'Visos klasės');
                applyFiltersToCalendar();
            });
            
            // Add clear button handler
            $('#clearClassSelection').on('click', function(e) {
                e.preventDefault();
                classButtonSelector.find('.class-button').removeClass('selected');
                updateDropdownText('#classCustomDropdown', 'Visos klasės');
                applyFiltersToCalendar();
            });
            
            // Add dropdown toggle handler
            $('#classCustomDropdown .custom-dropdown-trigger').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleCustomDropdown('#classCustomDropdown');
            });
            

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
        // Collect filter values from dropdowns (now supporting multiple values)
        const filters = {};
        
        const locationValues = $('#locationDropdown').val();
        if (locationValues && locationValues.length > 0 && locationValues[0] !== '') {
            filters.locations = locationValues.filter(val => val !== '');
        }
        
        const activityValues = $('#activityDropdown').val();
        if (activityValues && activityValues.length > 0 && activityValues[0] !== '') {
            filters.activities = activityValues.filter(val => val !== '');
        }
        
        const difficultyValues = $('#difficultyDropdown').val();
        if (difficultyValues && difficultyValues.length > 0 && difficultyValues[0] !== '') {
            filters.difficulties = difficultyValues.filter(val => val !== '');
        }
        
        // Get age values from custom button selector
        const selectedAgeButtons = $('#ageButtonSelector .age-button.selected');
        if (selectedAgeButtons.length > 0) {
            filters.ages = selectedAgeButtons.map(function() {
                return $(this).data('value');
            }).get();
        }
        
        // Get class values from custom button selector
        const selectedClassButtons = $('#classButtonSelector .class-button.selected');
        if (selectedClassButtons.length > 0) {
            filters.classes = selectedClassButtons.map(function() {
                return $(this).data('value');
            }).get();
        }
        
        const teacherValues = $('#teacherDropdown').val();
        if (teacherValues && teacherValues.length > 0 && teacherValues[0] !== '') {
            filters.teachers = teacherValues.filter(val => val !== '');
        }
        
        // Remove existing event sources to prevent duplicates
        calendar.removeAllEventSources();
        
        // Create new event source with filters
        calendar.addEventSource(function(fetchInfo, successCallback, failureCallback) {
            loadEventsFromAPI(function(events) {
                successCallback(events);
                
                // Check if current week is empty after applying filters and navigate if needed
                setTimeout(() => {
                    checkAndNavigateToFirstWeekWithEvents(events);
                }, 100);
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
    
    // Check if current week is empty and navigate to first week with events
    function checkAndNavigateToFirstWeekWithEvents(events) {
        if (!calendar || !events || events.length === 0) {
            return;
        }
        
        // Get current calendar view date range
        const currentView = calendar.view;
        const currentStart = currentView.activeStart;
        const currentEnd = currentView.activeEnd;
        
        // Check if any events exist in current view range
        const eventsInCurrentView = events.filter(event => {
            const eventDate = new Date(event.start);
            return eventDate >= currentStart && eventDate < currentEnd;
        });
        
        // If current view has events, don't navigate
        if (eventsInCurrentView.length > 0) {
            return;
        }
        
        // Find the earliest event date
        let earliestEventDate = null;
        events.forEach(event => {
            const eventDate = new Date(event.start);
            if (!earliestEventDate || eventDate < earliestEventDate) {
                earliestEventDate = eventDate;
            }
        });
        
        // Navigate to the earliest event date if found
        if (earliestEventDate) {
            // Navigate calendar to the week/day containing the earliest event
            calendar.gotoDate(earliestEventDate);
            


        }
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
            
            // Helper function to parse comma-separated values
            function parseFilterValue(value) {
                if (!value) return null;
                return value.split(',').map(v => v.trim()).filter(v => v !== '');
            }
            
            // Set dropdown values based on initial filters (supporting comma-separated values)
            if (filters.location) {
                const locationValues = parseFilterValue(filters.location);
                if (locationValues && locationValues.length > 0) {
                    $('#locationDropdown').val(locationValues).trigger('change');
                }
            }
            if (filters.activity) {
                const activityValues = parseFilterValue(filters.activity);
                if (activityValues && activityValues.length > 0) {
                    $('#activityDropdown').val(activityValues).trigger('change');
                }
            }
            if (filters.teacher) {
                const teacherValues = parseFilterValue(filters.teacher);
                if (teacherValues && teacherValues.length > 0) {
                    $('#teacherDropdown').val(teacherValues).trigger('change');
                }
            }
            if (filters.level) {
                const levelValues = parseFilterValue(filters.level);
                if (levelValues && levelValues.length > 0) {
                    $('#difficultyDropdown').val(levelValues).trigger('change');
                }
            }
            if (filters.age) {
                const ageValues = parseFilterValue(filters.age);
                if (ageValues && ageValues.length > 0) {
                    // Select age buttons based on values
                    ageValues.forEach(value => {
                        $(`#ageButtonSelector .age-button[data-value="${value}"]`).addClass('selected');
                    });
                    updateDropdownText('#ageCustomDropdown', 'Visi amžiai');
                }
            }
            if (filters.class) {
                const classValues = parseFilterValue(filters.class);
                if (classValues && classValues.length > 0) {
                    // Select class buttons based on values
                    classValues.forEach(value => {
                        $(`#classButtonSelector .class-button[data-value="${value}"]`).addClass('selected');
                    });
                    updateDropdownText('#classCustomDropdown', 'Visos klasės');
                }
            }
            
            // Apply the filters if any are set
            if (filters.location || filters.activity || filters.teacher || filters.level || filters.age || filters.class) {
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