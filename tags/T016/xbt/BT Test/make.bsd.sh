clear
g++ -DBSD -DNDEBUG -I ../misc -I . -O3 -lz -o xbt_client_backend *.cpp ../misc/*.cpp && strip xbt_client_backend
