clear
g++ -DNDEBUG -I /usr/local/include -I ../misc -I . -O3 -lmysqlclient -o xbt_tracker *.cpp ../misc/*.cpp ../misc/sql/*.cpp && strip xbt_tracker
