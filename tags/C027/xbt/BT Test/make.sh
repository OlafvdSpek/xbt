clear
g++ -DNDEBUG -I ../misc -I . -O3 -lz -o xbt_client_back_end *.cpp ../misc/*.cpp && strip xbt_client_back_end
