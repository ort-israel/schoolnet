/* jshint ignore:start */
define(['jquery','core/log'], function($, log) {

    "use strict"; // jshint ;_;

    log.debug('Universal Uploader: initialising');

    return {
    
    	config: null,
    	
    	init: function(element,config){
    		this.config = config;
    		this.insert_controls(element);
    	},

	insert_controls: function(element){     
         //progress
			var controls='<div id="' + this.config.widgetid + '_progress" class="p_progress x"><p></p></div>';
			controls += '<div id="' + this.config.widgetid + '_messages" class="p_messages x"></div>';
			$(element).append(controls);  
		},
        
        uploadBlob: function(blob,filetype){
            this.uploadFile(blob, filetype);
            return;
        },
        //extract filename from the text returned as response to upload
        extractFilename: function(returntext){
            var searchkey ="success<filename>";
        	 var start= returntext.indexOf(searchkey);
			if (start<1){return false;}
			var end = returntext.indexOf("</filename>");
			var filename= returntext.substring(start+(searchkey.length),end);
			return filename;
        },
        //create a progress bar
        createProgressBar: function(xhr,uploader){
            var progress=false;
        	var o_query = $("#" + uploader.config.widgetid + "_progress");
			//if we got one
			if(o_query.length){
				//get the dom object so we can use direct manip.
				var o = o_query.get(0);
				progress = o.firstChild;
				if(progress==null){
					progress = o.appendChild(document.createElement("p"));
				}
				//reset/set background position to 0, and label to "uploading
				progress.className="";
				progress.style.display = "block";
				progress.style.backgroundPosition = "100% 0";

				// progress bar
				xhr.upload.addEventListener("progress", function(e) {
					var pc = parseInt(100 - (e.loaded / e.total * 100));
					progress.style.backgroundPosition = pc + "% 0";
				}, false);
			}
           return progress;
        },
        //fetch file extension from the filetype
        fetchFileExtension: function(filetype){
        	var ext="";
        	//Might need more mimetypes than this, and 3gpp maynot work
            switch(filetype){
                case "image/jpeg": ext = "jpg";break;
                case "image/png": ext = "png";break;
                case "audio/wav": ext = "wav";break;
                case "video/quicktime": ext = "mov";break;
                case "audio/mpeg3": ext = "mp3";break;
                case "audio/webm": ext = "webm";break;
                case "audio/x-mpeg-3": ext = "mp3";break;
                case "audio/mpeg3": ext = "mp3";break;
                case "audio/3gpp": ext = "3gpp";break;
                case "video/mpeg3": ext = "3gpp";break;
                case "video/mp4": ext = "mp4";break;
                case "video/webm": ext = "webm";break;
            }
            return ext;
        },
        
        pokeFilename: function(filename,uploader){
            uploader.Output(M.util.get_string('recui_uploadsuccess', 'filter_poodll'));
            var upc = $('#' + uploader.config.updatecontrol);
            if (upc.length > 0) {
                    upc.get(0).value = filename;
            }else{
                    upc = window.parent.document.getElementById(uploader.config.updatecontrol);
                    if(upc){
                            upc.value = filename;
                    }else{
                            log.debug('upload failed #2');
                            log.debug(xhr);
                            uploader.Output(M.util.get_string('recui_uploaderror', 'filter_poodll'));
                            return false
                    }
            }
            
            return true;
        },
        
        
        //after an upload handle the filename poke and callback call
        postProcessUpload: function(e,uploader){
        	  var xhr = e.currentTarget;
			  if (xhr.readyState == 4 ) {
				if(xhr.status==200){
					var filename = uploader.config.filename;
					if(!filename){
						filename = uploader.extractFilename(xhr.responseText);
					}
					if(!filename){
						log.debug('upload failed #1');
						log.debug(xhr);
						return;
					}
				
					//invoke callbackjs if we have one, otherwise just update the control(default behav.)
					if(uploader.config.callbackjs && uploader.config.callbackjs !=''){
						var callbackargs  = new Array();
						callbackargs[0]=uploader.config.widgetid;
						callbackargs[1]="filesubmitted";
						callbackargs[2]=filename;
						callbackargs[3]=uploader.config.updatecontrol;
				  
						this.Output(M.util.get_string('recui_uploadsuccess', 'filter_poodll'));
						this.executeFunctionByName(uploader.config.callbackjs,window,callbackargs);

					}else {
                                            uploader.pokeFilename(filename,uploader);

					}
				}else{
					log.debug('upload failed #3');
					log.debug(xhr);
					uploader.Output(M.util.get_string('recui_uploaderror', 'filter_poodll'));
				} //end of if status 200
			}//end of if ready state 4
        
        },
       
        // upload Media file to wherever
        uploadFile: function(filedata,filetype) {
      
            var xhr = new XMLHttpRequest();
			var config = this.config;
			var uploader = this;
			
            //get the file extension from the filetype
            var ext = this.fetchFileExtension(filetype);
 
			var using_s3 = config.using_s3;

			// create progress bar if we have a container for it
			var progress = this.createProgressBar(xhr,uploader);
 
            //alert user that we are now uploading    
            this.Output(M.util.get_string('recui_uploading', 'filter_poodll'));

			xhr.upload.addEventListener("load", function () {
				//console.log("uploaded:");
                                if(using_s3){
                                 //ping Moodle and inform that we have a new file
                                    uploader.postprocess_s3_upload(uploader);
                                }
			});

			
			xhr.onreadystatechange = function(e){
				uploader.postProcessUpload(e,uploader);
			}
			
			if(using_s3){
				xhr.open("put",config.posturl, true);
				xhr.setRequestHeader("Content-Type", 'application/octet-stream');
				xhr.send(filedata);
                                
                          
                                
			}else{
				//log.debug(params);
			   	var params = "datatype=uploadfile";
				//We must URI encode the filedata, because otherwise the "+" characters get turned into spaces
				//spent hours tracking that down ...justin 20121012
				params += "&paramone=" + encodeURIComponent(filedata);
				params += "&paramtwo=" + ext;
				params += "&paramthree=" + config.mediatype;
				params += "&requestid=" + config.widgetid;
				params += "&contextid=" + config.p2;
				params += "&component=" + config.p3;
				params += "&filearea=" + config.p4;
				params += "&itemid=" + config.p5;
			
				xhr.open("POST",config.posturl, true);
				xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				xhr.setRequestHeader("Cache-Control", "no-cache");
				xhr.setRequestHeader("Content-length", params.length);
				xhr.setRequestHeader("Connection", "close");
				xhr.send(params);
			}//end of if using_s3
        },
      
        postprocess_s3_upload: function(uploader){
            var config = uploader.config;
            var xhr = new XMLHttpRequest();

            //log.debug(params);
            var params = "datatype=handles3upload";
            params += "&contextid=" + config.p2;
            params += "&component=" + config.p3;
            params += "&filearea=" + config.p4;
            params += "&itemid=" + config.p5;
            params += "&filename=" + config.filename;
            params += "&mediatype=" + config.mediatype;

            xhr.open("POST",M.cfg.wwwroot + '/filter/poodll/poodllfilelib.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.setRequestHeader("Content-length", params.length);
            xhr.setRequestHeader("Connection", "close");
            xhr.send(params);
            
        },
            
        // output information
        Output: function(msg) {
            var m = $("#" + this.config.widgetid + "_messages");
            m.text(msg);
        },
            
            //function to call the callback function with arguments
        executeFunctionByName: function(functionName, context , args ) {

            //var args = Array.prototype.slice.call(arguments).splice(2);
            var namespaces = functionName.split(".");
            var func = namespaces.pop();
            for(var i = 0; i < namespaces.length; i++) {
                context = context[namespaces[i]];
            }
            return context[func].call(this, args);
        }

    }//end of returned object
});//total end