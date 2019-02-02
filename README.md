# XBT Tracker

### Installing under Linux

The following commands can be used to install the dependencies on Debian and Ubuntu. The g++ version should be at least 6.

```
apt install cmake default-libmysqlclient-dev g++ git libboost-dev make zlib1g-dev
```

The following commands can be used to install some of the dependencies on CentOS, Fedora and Red Hat. The g++ version should be at least 6.

```
yum install boost-devel cmake gcc-c++ git mysql-devel
```

Enter the following commands in a terminal. Be patient while g++ is running, it'll take a few minutes.

```
git clone https://github.com/OlafvdSpek/xbt
cd xbt/Tracker
cmake .
make
cp xbt_tracker.conf.default xbt_tracker.conf
```
