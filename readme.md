# Emoncms v5.0 - timestore development branch

1) Download, make and start timestore

$ git clone http://mikestirling.co.uk/git/timestore.git
$ cd timestore
$ make
$ cd src
$ sudo ./timestore -d

Fetch the admin key

cd /var/lib/timestore
nano adminkey.txt

copy the admin key which looks something like this: POpP)@H=1[#MJYX<(i{YZ.0/Ni.5,g~<
the admin key is generated anew every time timestore is restarted.

2) Download and setup the emoncms timestore branch

Download copy of the timestore development branch

git clone -b timestore https://github.com/emoncms/emoncms.git timestore

Create a mysql database for emoncms and enter database settings into settings.php.

Add a line to settings.php with the timestore adminkey:
$timestore_adminkey = "POpP)@H=1[#MJYX<(i{YZ.0/Ni.5,g~<";

Create a user and login

The development branch currently only implements timestore for realtime data and the feed/data api is restricted to timestore data only which means that daily data does not work. The use of timestore for daily data needs to be implemented.

The feed model methods implemented to use timestore so far are create, insert_data and get_data.

Try it out

Navigate to the feeds tab, click on feed API helper, create a new feed by typing:

http://localhost/timestore/feed/create.json?name=power&type=1

It should return {"success":true,"feedid":1}

Navigate back to feed you should now see your power feed in the list.

Navigate again to the api helper to fetch the insert data api url

Call the insert data api a few times over say a minute (so that we have at least 6 datapoints - one every 10 seconds)
Vary the value to make it more interesting:

http://localhost/timestore/feed/insert.json?id=1&value=100.0

Select the rawdata visualisation from the vis menu
http://localhost/timestore/vis/rawdata&feedid=1

zoom to the last couple of minutes to see the data.


