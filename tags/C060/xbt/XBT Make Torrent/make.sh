clear
g++ -DNDEBUG -I ../misc -I . -O3 -lz -o xbt_make_torrent *.cpp ../misc/*.cpp && strip xbt_make_torrent
