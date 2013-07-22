## Emoncms powered by timestore (dev)

Timestore is time-series database designed specifically for time-series data developed by Mike Stirling.

[mikestirling.co.uk/redmine/projects/timestore](mikestirling.co.uk/redmine/projects/timestore)

**Faster Query speeds**
With timestore feed data query requests are about 10x faster (2700ms using mysql vs 210ms using timestore).
*Note:* initial benchmarks show timestore request time to be around 45ms need to investigate the slightly slower performance may be on the emoncms end rather than timestore.

**Reduced Disk use**
Disk use is also much smaller, A test feed stored in an indexed mysql table used 170mb, stored using timestore which does not need an index and is based on a fixed time interval the same feed used 42mb of disk space. 

**In-built averaging**
Timestore also has an additional benefit of using averaged layers which ensures that requested data is representative of the window of time each datapoint covers.

## Setup and test

The instructions below are for setting up a new installation of emoncms using timestore on the raspberrypi and dont yet cover upgrading or conversion of existing feeds and wont yet work with existing input processing settings. 

If you want to test timestore while maintaining your current setup, create the timestore installation in a new folder and create a new mysql database for use with it.

### 1) Download, make and start timestore

    cd /home/pi
    git clone https://github.com/mikestir/timestore.git
    cd timestore
    git checkout float
    make
    sudo mkdir /var/lib/timestore
    cd /home/pi/timestore/src
    sudo ./timestore

*Note:* run "sudo ./timestore -d" to run in terminal, not as a deamon.

### 2) Use timestore branch of emoncms

Switch an existing installation over to the timestore branch:

    cd /var/www/emoncms
    git pull
    git checkout timestore

or to create a new emoncms installation in a folder called timestore: 

    cd /var/www
    git clone -b timestore https://github.com/emoncms/emoncms.git timestore

### 3) Use timestore branch of raspberrypi

    cd /var/www/timestore/Modules/raspberrypi (or cd /var/www/emoncms/Modules/raspberrypi)
    git pull
    git checkout timestore

### 4) Fetch the timestore admin key

    cd /var/lib/timestore
    nano adminkey.txt

copy the admin key which looks something like this: POpP)@H=1[#MJYX<(i{YZ.0/Ni.5,g~<
the admin key is generated a new every time timestore is restarted.

### 5) Settings.php

Open to edit settings.php

    cd /var/www/timestore (or cd /var/www/emoncms)
    rm settings.php
    cp default.settings.php settings.php
    nano settings.php

Insert mysql database settings, create a new mysql database if necessary.
Insert timestore admin apikey as found in step 4 above.

### 6) Update database

If your using an existing database login with your admin user and run database update. If you have created a new database you should only need to create a user and login, the database tables will get created when you first load emoncms.

### Try it out

Setup the raspberrypi module as usual, make sure the gateway script is running.
Use input processing to create timestore feeds, set the fixed data interval rate to that of your monitoring hardware. Try out the rawdata, bargraph and multigraph visualisations.


### Blog posts:

[http://openenergymonitor.blogspot.co.uk/2013/06/timestore-timeseries-database.html](http://openenergymonitor.blogspot.co.uk/2013/06/timestore-timeseries-database.html)
[http://openenergymonitor.blogspot.co.uk/2013/06/rethinking-data-input-and-storage-core.html](http://openenergymonitor.blogspot.co.uk/2013/06/rethinking-data-input-and-storage-core.html)
[http://openenergymonitor.blogspot.co.uk/2013/07/more-direct-file-storage-research.html](http://openenergymonitor.blogspot.co.uk/2013/07/more-direct-file-storage-research.html)
