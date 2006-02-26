clear
g++ -DNDEBUG -I ../misc -I . -O3 -lz -o xbt_client_cli *.cpp ../misc/*.cpp && strip xbt_client_cli
