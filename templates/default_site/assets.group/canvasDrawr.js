/*
* This script performs several functions with joint purposes related to painting on a canvas.
* 1. Draw on a canvas
* 2. Maintain an image on the canvas when canvas resizes
* 3. Draw an pre-determined image to the canvas
* 4. Clear the canvas
* 5. Saves every time the user stops drawing
*
* In order to provide all these abilities, it is expected that the "id" parameter is used across
*   several HTML tags as a class or id attribute identifier.
* The following elements are expected to be on the HTML document:
* 1. <canvas> (class attribute)
* 2. <input type="button"> OR <button> (class)
* 3. <input type="hidden"> (name)
*
* "name" is used on the hidden field for form submission in the POST array.
* "hidden" type input should hold a base64 encoded string for an
*   image, like toDataURL( ) would generate
*/
var canvasDrawr = function(options) {
    // Grab canvas element
    var canvas = $('canvas.'+options.name)[0],
        ctxt = canvas.getContext("2d");

    ctxt.pX = undefined;
    ctxt.pY = undefined;

    // Tracks whether or not the user is click-and-dragging
    var is_move = false;
    // This is the only way to keep track of mouse actions
    var mouse_id = 0;
    var lines = [];
    var offset;
    
    var self = {
        init: function() {
            // These 3 lines keep the canvas from 'zooming', which ensures the line follows exactly where the points indicate
            canvas.style.width = '100%';
            canvas.width = canvas.offsetWidth;

            ctxt.lineWidth = options.size;
            ctxt.lineCap = options.lineCap || "round";

            offset = $(canvas).offset();

            // Reset the canvas - the selector reacts to <input type="button" /> or <button>
            $('button.'+options.name+', input[type=button].'+options.name).click(function() {
                ctxt.setTransform(1, 0, 0, 1, 0, 0);
                ctxt.clearRect(0, 0, canvas.width, canvas.height);

                 $('input[name="'+options.name+'"]').val('');

            });

            // Load signature if image is found in hidden field
            var img = new Image();
            img.src = $('input[name="'+options.name+'"]').val();
            
            img.onload = function() {
                ctxt.drawImage(img, 0, 0);
            };

            // Keep the current image on the canvas when resizing
            $(window).resize(function() {
                // Create a temporary canvas obj to cache the pixel data
                var temp_cnvs = document.createElement('canvas');
                var temp_cntx = temp_cnvs.getContext('2d');
               
                // Set it to the new width & height and draw the current canvas data into it
                temp_cnvs.width = canvas.offsetWidth;
                temp_cnvs.height = canvas.offsetHeight;
                temp_cntx.drawImage(canvas, 0, 0);
            
                // Resize & clear the original canvas and copy back in the cached pixel data
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;

                ctxt.lineWidth = options.size;
                ctxt.lineCap = options.lineCap || "round";

                ctxt.drawImage(temp_cnvs, 0, 0);

                offset = $(canvas).offset();
            });
            
            // Bind click events
            canvas.addEventListener('touchstart', self.preMove, false);
            canvas.addEventListener('touchmove', self.move, false);
            canvas.addEventListener('touchend', self.save, false);

            canvas.addEventListener('mousedown', self.mouse_startMove, false);
            canvas.addEventListener('mousemove', self.mouse_move, false);
            window.addEventListener('mouseup',   self.mouse_endMove, false);
            
        },

        preMove: function(event) {

            // Each time the canvas is touched...
            $.each(event.touches, function(i, touch) {
                // Give the touch event an identifier
                var id      = touch.identifier;
                
                // Determine event's position on the canvas
                lines[id] = { x     : this.pageX - offset.left,
                              y     : this.pageY - offset.top
                            };
            });

            event.preventDefault();
        },

        move: function(event) {

            $.each(event.touches, function(i, touch) {
                // Dynamically find the position of X,Y
                var id = touch.identifier,
                    moveX = this.pageX - offset.left - lines[id].x,
                    moveY = this.pageY - offset.top - lines[id].y;

                var ret = self.draw(id, moveX, moveY);
                lines[id].x = ret.x;
                lines[id].y = ret.y;
            });

            event.preventDefault();
        },

        draw: function(i, changeX, changeY) {
            ctxt.strokeStyle = "black";
            ctxt.beginPath();
            if(is_move){
                ctxt.moveTo(lines[i-1].x, lines[i-1].y);
            } else {
                ctxt.moveTo(lines[i].x, lines[i].y);
            }
            

            ctxt.lineTo(lines[i].x + changeX, lines[i].y + changeY);
            ctxt.stroke();
            ctxt.closePath();

            return { x: lines[i].x + changeX, y: lines[i].y + changeY };
        },
        
        // Write base 64 image to a hidden element so the image can be passed to PHP
        save: function(event) {
            $('input[name="'+options.name+'"]').val( canvas.toDataURL() );
        },

        mouse_startMove: function(event) {

            // Position on the canvas found as coords on the page minus the offset of the canvas
            lines[mouse_id] = {
                x     : event.pageX - offset.left,
                y     : event.pageY - offset.top
            };
            is_move = true;
            mouse_id++;
            event.preventDefault();
        },

        mouse_endMove: function(event) {
            is_move = false;
            self.save(event);
            event.preventDefault();
        },

        mouse_move: function(event) {

            if (is_move) {
                lines[mouse_id] = {
                            x     : event.pageX - offset.left,
                            y     : event.pageY - offset.top
                };

                var moveX = event.pageX - offset.left - lines[mouse_id].x,
                    moveY = event.pageY - offset.top - lines[mouse_id].y;
              
                var ret = self.draw(mouse_id, moveX, moveY);
                lines[mouse_id].x = ret.x;
                lines[mouse_id].y = ret.y;
                mouse_id++;
            }
            
            event.preventDefault();
        }
    };

    return self.init();
};