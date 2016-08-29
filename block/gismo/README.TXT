===================================================================================
                                      G I S M O
                    
                          Version 3.4 - April 12, 2016 
		
		Università della svizzera italiana, Lugano, Switzerland
                    
                                       README
===================================================================================

0)  Requirements

    Gismo is primarily developed on the LAMP platform (Linux, Apache, MySQL and 
    PHP), we didn't test it in Windows environments.

    The requirements for Gismo are:
    - PHP v. 5.4.4
    - MySQL 5.5.31
    - Moodle 2.9.x

1)  Setup
    
    a) REMOVE OLD VERSION

    Please remove any old version of the plugin.

    b) INSTALL FILES    
    
    Please follow the standard procedure to install Moodle plugins:
    - copy the gismo folder into the moodle blocks directory (MOODLE_DOCUMENT_ROOT/blocks)
    - logon to moodle as administrator and visit the Notifications page
    - configure the new settings page

    With git:
        git clone https://steveorulez@bitbucket.org/steveorulez/block_gismo.git gismo

    c) LAUNCH DATA EXPORTER FOR THE FIRST TIME
    
    Gismo needs to perform some computation on regular basis. The purpose of this 
    computation is to extract data from logs and derive aggregated information. 
    
    This computation is done by a scheduled task, by default it runs at 2am (server time)
    but can be changed in Site Administration -> Server -> Scheduled tasks -> GIMSO data exporter task

    When you install  Gismo block and you don't want to wait the next day to see the result,
    you can run the task manually (ONLY FROM CLI):
    php admin/tool/task/cli/schedule_task.php --execute=\\block_gismo\\task\\export_data

2)  Configuration

    Gismo doesn't need any specific configuration. Everything runs out of the box.

3)  Use
    
    Please access your course as teacher and add a Gismo block instance, then follows instructions.

4)  Further information
    
    Please visit http://gismo.sourceforge.net/ for further information
    
5)  Gismo is released under GPL version 3 License
    
    
The eLab Team
    
===================================================================================