clear
g++ -DBSD -DNDEBUG -I /usr/local/include -I ../misc -I . -L /usr/local/lib/mysql -O3 -lmysqlclient -o xbt_tracker *.cpp ../misc/*.cpp ../misc/sql/*.cpp && strip xbt_tracker
