clear
g++ -DNDEBUG -I ../misc -I . -O3 -lboost_program_options -lz -o xbt_client_backend *.cpp ../misc/*.cpp && strip xbt_client_backend
