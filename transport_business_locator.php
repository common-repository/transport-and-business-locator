<?php
/* Plugin Name: Transport and Business Locator 
 * Plugin URI: http://bobcares.com
 * Description: This plugin will help the users to find the bus stations, stores, atm's etc within a particular radius of the searched location. Users will be able to configure the radius and the locators from the settings page.
 * Version: 1.2.0
 * Author: Bobcares <pm@bobcares.com>
 * Author URI: http://bobcares.com
 * License:
 */

/*
 * function to display contents in the webpage
 * @param null
 * @return display contents in a webpage
 */

if (!function_exists('writeLog')) {

    /**
     * Function to add the plugin log to wordpress log file, added by BDT
     * @param object $log
     */
    function writeLog($log, $line = "", $file = "") {

        if (WP_DEBUG === true) {

            $pluginLog = $log . " on line [" . $line . "] of [" . $file . "]\n";

            if (is_array($pluginLog) || is_object($pluginLog)) {
                print_r($pluginLog, true);
            } else {
                error_log($pluginLog);
            }
        }
    }

}

function locationDisplay() {

    //Setting default values for latitude, longitude and the locator type
    $address = "Dallas";
    $skeyword = "";
    ob_start();
    mapDisplay();
    $output = ob_get_clean();
    return $output;
}

function mapDisplay() {
    // Handling the form post values for address and the locator type
    if (isset($_POST['search'])) {
        $address = sanitize_text_field(trim($_POST['address']));
        $skeyword = sanitize_text_field($_POST['skeyword']);
        writeLog(" address " . $address . " and locator type " . $skeyword . " are posted", basename(__LINE__), basename(__FILE__));
    }
    ?>
    <html>
        <head>
            <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
            <meta charset="utf-8">
            <title>Place search pagination</title>
            <style>
                html, body {
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }
                #map {
                    min-height: 500px;

                }
                #right-panel {
                    font-family: 'Roboto','sans-serif';
                    line-height: 30px;
                    padding-left: 10px;
                }

                #right-panel select, #right-panel input {
                    font-size: 15px;
                }

                #right-panel select {
                    width: 100%;
                }

                #right-panel i {
                    font-size: 12px;
                }
                #right-panel {
                    background: #fff none repeat scroll 0 0;
                    border: 1px solid #999;
                    font-family: Arial,Helvetica,sans-serif;
                    z-index: 5;
                    right: 0!important;
                    width: 30% !important;
                }
                h2 {
                    font-size: 22px;
                    margin: 0 0 5px 0;
                }
                #right-panel ul {
                    list-style-type: none;
                    padding: 0;
                    margin: 0;
                    height: 271px;
                    width: 100%;
                    overflow-y: scroll;
                }
                #right-panel li {

                    padding: 10px;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    overflow: hidden;
                }
                li:hover {
                    background-color: #f1f1f1;
                }
                #more {
                    width: 100%;
                    margin: 5px 0 0 0;
                }
            </style>


            <script>
                // This example requires the Places library. Include the libraries=places
                // parameter when you first load the API. For example:
                // <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

                var map;
                var pos;
                var locate;
                var radius;
                var _MARKER_ARRAY = new Array();

                /***
                 * Function Name : initMap
                 * usage : for initializing the map
                 * @param  null
                 * @returns null
                 */
                function initMap() {
                    var pyrmont = {lat: 9.931233, lng: 76.267303};

                    map = new google.maps.Map(document.getElementById('map'), {
                        center: pyrmont,
                        zoom: 17
                    });
                    document.getElementById('right-panel').style.display = "none";
                    Autocomplete();
                }

                /***
                 * Function Name : processResults
                 * usage : for processing the result after the place and locator are submit 
                 * @param string result , string status , int pagination
                 * @returns Null
                 */
                function processResults(results, status, pagination) {

                    //check status
                    if (status !== google.maps.places.PlacesServiceStatus.OK) {
                        alert("No " + locate + " found.");
                        return;
                    } else {
                        createMarkers(results);

                    }
                }
                var infoWindowArray = new Array();

                /***
                 * Name : clearMarker
                 * Usage : for clearing the marker defined 
                 * @param : null
                 * @returns null
                 */
                function clearMarker() {
                    var placesList = document.getElementById('places');
                    placesList.innerHTML = "";

                    //loop to get all places
                    for (var i = 0; i < _MARKER_ARRAY.length; i++) {
                        _MARKER_ARRAY[i].setMap(null);
                    }
                    _MARKER_ARRAY.length = 0;
                    document.getElementById('right-panel').style.display = "none";
                }

                /***
                 * Name : createMarker
                 * Usage : for creating the marker for places 
                 * @param {object} places
                 * @returns null
                 */
                function createMarkers(places) {

                    console.log(places);
                    var bounds = new google.maps.LatLngBounds();
                    var placesList = document.getElementById('places');
                    clearMarker();
                    var infowindow = new google.maps.InfoWindow();

                    //loop to get the places 
                    for (var i = 0, place; place = places[i]; i++) {
                        if (place.types[0] != locate) {
                            continue;
                        }
                        var image = {
                            url: place.icon,
                            size: new google.maps.Size(71, 71),
                            origin: new google.maps.Point(0, 0),
                            anchor: new google.maps.Point(17, 34),
                            scaledSize: new google.maps.Size(25, 25)
                        };
                        var marker = new google.maps.Marker({
                            map: map,
                            icon: image,
                            title: place.name,
                            position: place.geometry.location
                        });
                        google.maps.event.addListener(marker, 'click', (function (marker, place) {
                            return function () {
                                infowindow.setContent('<div><strong>' + place.name + '</strong><br>' +
                                        place.vicinity + '</div>');
                                infowindow.setOptions({maxWidth: 200});
                                infowindow.open(map, marker);
                            }
                        })(marker, place));
                        var li = document.createElement("li");
                        li.innerHTML = place.name;
                        li.style.cursor = "pointer";
                        placesList.appendChild(li);
                        _MARKER_ARRAY.push(marker);
                        //placesList.innerHTML += '<li>' + place.name + '</li>';

                        /***
                         * function to create marker in map
                         * @param {string} mark
                         * @param {array} pl
                         * @returns {undefined}                         */
                        (function (mark, pl) {
                            console.log(pl);
                            li.addEventListener('click', function () {
                                infowindow.setContent('<div><strong>' + pl.name + '</strong><br>' +
                                        pl.vicinity + '</div>');
                                infowindow.setOptions({maxWidth: 200});
                                infowindow.open(map, mark);
                            });
                        })(marker, place);

                        bounds.extend(place.geometry.location);
                    }

                    //if no marker /places is there to show in map
                    if (_MARKER_ARRAY.length == 0) {
                        alert("No " + locate + " found.");
                    } else {
                        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(document.getElementById('right-panel'));
                        document.getElementById('right-panel').style.display = "block";
                        document.getElementById("download").style = "display: block";
                    }
                    map.fitBounds(bounds);
                }


                /***
                 * Name : Autocomplete
                 * Usage : to autocomplete the places inserted in search box
                 * @param : null
                 * @returns null                 
                 * */
                function Autocomplete() {
                    document.getElementById('select').addEventListener('change', function () {
                        document.getElementById('pac-input').value = "";
                        locate = document.getElementById('select').value;
                        clearMarker();
                    });

                    // Create the search box and link it to the UI element.
                    var input = document.getElementById('pac-input');
                    var searchBox = new google.maps.places.SearchBox(input);


                    // Bias the SearchBox results towards current map's viewport.
                    map.addListener('bounds_changed', function () {
                        searchBox.setBounds(map.getBounds());
                    });

                    var markers = [];

                    // Listen for the event fired when the user selects a prediction and retrieve
                    // more details for that place.
                    searchBox.addListener('places_changed', function () {
                        var places = searchBox.getPlaces();

                        //if there is no place
                        if (places.length == 0) {
                            return;
                        } else {
                            places.forEach(function (place) {
                                pos = place.geometry.location;
                            });
                            var position = {
                                "lat": pos.lat(),
                                "lng": pos.lng()
                            }
                            var typeSearch = new Array(locate);

                            //if selected radius is less than 10
                            if (<?php echo get_option('radius'); ?> <= 100) {
                                radius = 500;

                            } else {
                                radius = <?php echo get_option('radius'); ?>;

                            }

                            var service = new google.maps.places.PlacesService(map);
                            service.nearbySearch({
                                location: position,
                                radius: radius,
                                type: typeSearch
                            }, processResults);
                        }

                    });
                }

            </script>

            <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7MJimPYaxyzB5vZww56t9DK2nWTceHt4&libraries=places&callback=initMap" async defer></script>
            <script src="<?php echo plugin_dir_url(__FILE__) . 'excellentexport.js' ?>"></script>
        </head>
        <body>
            <br>
            <div class='tblContent'>
                <div id="search">
                    <select id="select" name="skeyword">
                        <option selected="selected">Choose an option</option>
                        <?php
                        $values = get_option('locations');

                        foreach ($values as $locate) {
                            echo '<option value="' . $locate . '">' . $locate . '</option>';
                        }
                        ?>
                    </select>
                    <br>
                    <br><input id="pac-input" class="controls" type="text" placeholder="Please enter the place or location ">

                </div>

                <div id="right-panel">
                    <h2>Results</h2>
                    <ul id="places"></ul>
                    <!-- Download button -->
                    <div id="download" style="display: none;">
                        <a download="data.xls" href="#"
                           onclick="return ExcellentExport.excel(this, 'places', 'places');">Export
                            to Excel</a>
                    </div>  
                </div> 
                <br>
                <div id="map"></div>
            </div>
        </body>
    </html>
    <?php
}

//Adding a shortcode for displaying the location details
add_shortcode('locDisplay', 'locationDisplay');

add_action('admin_menu', 'placeLocator');

function placeLocator() {
    add_menu_page('place_locator', 'Transport & Business Locator', 'read', 'my-unique-identifier', 'placeLocatorMenu');
    //  add_submenu_page('place_locator', 'New', 'read', 'my-unique-identifier', 'my_plugin_function');
}

function placeLocatorMenu() {
    ?>

    <html>
        <style>
            fieldset {
                margin-right: 0 auto;
                margin-left: 0 auto;
                margin-top: 10%;
                width: 20%;
                padding-left: 30%;
            }

            #multiSelect {
                width: 70%;
                margin-top: 20px;
                margin-bottom: 20px;
            }

            #radius {
                width: 70%;
                margin-top: 20px;
                margin-bottom: 20px;
                display: block;
            }

            #submit {
                width: 72%;
            }

            form {
                margin-top: 10px;
            }
            .tbl-h5 {
                margin:0;
            }
        </style>
        <br />
        <form method="POST">

            <?php
//Fetch the radius value
            $radius = get_option('radius');

//Storing the details
            $locations = get_option('locations');
            ?>

            <h2>Transport and Business Locator Options</h2>

            <h5>Please select the locator type and radius</h5>
            <table >
                <tr>
                    <td>Locator Type :</td>
                    <td><select multiple id="multiSelect" name="skeyword[]">

                            <!-- Modified to identify the selected options and display it -->
                            <!--<option selected="selected">Choose options</option>-->
                            <option <?php if ((is_array($locations)) && (in_array("church", $locations))) echo "selected"; ?> value="church">Church</option>
                            <option <?php if ((is_array($locations)) && (in_array("mosque", $locations))) echo "selected"; ?> value="mosque">Mosque</option>
                            <!--<option <?php //if ((is_array($locations)) && (in_array("movie-theater", $locations))) echo "selected";      ?> value="movie-theater">Movie Theater</option>-->
                            <option <?php if ((is_array($locations)) && (in_array("atm", $locations))) echo "selected"; ?> value="atm">ATM</option>
                            <option <?php if ((is_array($locations)) && (in_array("bank", $locations))) echo "selected"; ?> value="bank">Bank</option>
                            <option <?php if ((is_array($locations)) && (in_array("store", $locations))) echo "selected"; ?> value="store">Store</option>
                            <option <?php if ((is_array($locations)) && (in_array("pharmacy", $locations))) echo "selected"; ?> value="pharmacy">Pharmacy</option>
                        </select>
                    </td>
                </tr>

                <tr>

                    <!-- Modified by Sreenath to dispaly the radius value entered by the user -->
                    <td>
                        Radius :
                        <h5 class="tbl-h5">*Please add the radius above 100 to get best result*</h5>
                    </td>
                    <td><input id="radius" type="number" name="radius" required  placeholder="Please Add Radius Around Point Map" value = <?php echo $radius; ?>  >
                    </td>
                </tr>

                <tr>
                    <td><input id="submit" type="submit" name="submitsettings"
                               value="Submit">
                    </td>
                </tr>
            </table>
        </form>

        <?php
    }

    if (isset($_REQUEST['submitsettings'])) {

        //Removing the already existing values for radius and location
        delete_option('radius');
        delete_option('locations');

        //Initializing
        $location = '';

        //Sanitizng the values
        $radius = sanitize_text_field($_REQUEST['radius']);

        //Storing the radius value in options table
        add_option('radius', $radius);

        //Cannot use sanitize_text_field as the location list will be in serialized format
        $location = apply_filters("sanitize_option_locations", $_REQUEST['skeyword'], 'locations');

        //Storing the location list
        add_option('locations', $location);
    }

    /**
     * Name : transport_business_locator
     * @package    transport and business locator
     */
    class transport_business_locator extends WP_Widget {

        // constructor
        function transport_business_locator() {

            // Give widget name here
            parent::WP_Widget(false, $name = __('Transport & Business Locator', 'wp_widget_plugin'));
        }

        /*         * *
         * Name  : form 
         * usage : widget form creation
         * @param: array $instance
         * @return: null
         */

        function form($instance) {

            // Check values
            if ($instance) {
                $title = $instance['title'];
                $location = $instance['locatorType'];
                $radius = $instance['radius'];
            } else {
                $locatorType = '';
                $select = '';
            }
            ?>

            <style>
                .widget-content input, .radius {
                    width: 100%!important;
                }
                .tbl-h5 {
                    margin:0;
                }
            </style>
            <p>
                <b><label for="<?php echo $this->get_field_id("title"); ?>">
                        <?php _e("Title", "title") . " : "; ?>
                    </label></b>
                <input id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" value="<?php echo $instance["title"]; ?>" />
            </p>
            <p>
                <b><label for="<?php echo $this->get_field_id('locatorType'); ?>"><?php _e('Locator Type', 'wp_widget_plugin'); ?></label></b>
                <select multiple="multiple" id="<?php echo $this->get_field_id('locatorType'); ?>" class="widefat" name="<?php echo $this->get_field_name('locatorType'); ?>[]">
                    <option  <?php if ((is_array($location)) && (in_array('church', $location))) echo 'selected="selected"'; ?>value="church">Church</option>
                    <option  <?php if ((is_array($location)) && (in_array('mosque', $location))) echo 'selected="selected"'; ?>value="mosque">Mosque</option>
                    <!--<option  <?php //if ((is_array($location)) && (in_array('movie-theater' , $location))) echo 'selected="selected"';      ?>value="movie-theater">Movie Theater</option>-->
                    <option  <?php if ((is_array($location)) && (in_array('atm', $location))) echo 'selected="selected"'; ?>value="atm">ATM</option>
                    <option  <?php if ((is_array($location)) && (in_array('bank', $location))) echo 'selected="selected"'; ?>value="bank">Bank</option>
                    <option  <?php if ((is_array($location)) && (in_array('store', $location))) echo 'selected="selected"'; ?>value="store">Store</option>
                    <option  <?php if ((is_array($location)) && (in_array('pharmacy', $location))) echo 'selected="selected"'; ?>value="pharmacy">Pharmacy</option>
                </select>   
            </p>
            <p>

                <b><label for="<?php echo $this->get_field_id('radius'); ?>"><?php _e('Radius', 'wp_widget_plugin'); ?></label></b>
            <h5 class="tbl-h5">*Please add the radius above 100 to get best result*</h5>
            <input class="radius" id="<?php echo $this->get_field_id('radius'); ?>" name="<?php echo $this->get_field_name('radius'); ?>" type="number" value="<?php echo $radius; ?>" placeholder="Please Add Radius Around Point Map" />

        </p>
        <?php
    }

    /*     * *
     * Name  : update
     * usage : for updating the instance 
     * @param: array $new_instance, array $old_instance
     * @return: array $instance
     */

    function update($new_instance, $old_instance) {
        $instance = $old_instance;

        // Fields
        $instance['title'] = $new_instance['title'];
        $instance['locatorType'] = $new_instance['locatorType'];
        $instance['radius'] = $new_instance['radius'];
        return $instance;
    }

    /*     * *
     * Name  : widget
     * usage : for display widget 
     * @param: array $args, array $instance
     * @return: array null
     */

    function widget($args, $instance) {
        extract($args);


        /* Our variables from the widget settings. */
        $title = $instance["title"];

        // these are the widget options
        $locatorType = $instance['locatorType'];
        $radius = empty($instance['radius']) ? '' : $instance['radius'];
        echo $before_widget;
        echo "<h2>" . $title . "</h2>";

        //Removing the already existing values for radius and location
        delete_option('radius');
        delete_option('locations');

        //Initializing
        $location = '';

        //Storing the radius value in options table
        add_option('radius', $radius);

        //Storing the location list
        add_option('locations', $locatorType);

        mapDisplay();
        echo $after_widget;
    }

}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("transport_business_locator");'));
