g++ -DNDEBUG -I ../misc -I . -O3 -lboost_program_options-mt -lboost_system-mt -lz -o xbt_client_cli *.cpp ../misc/*.cpp && strip xbt_client_cli
