clear
g++ -DNDEBUG -I ../misc -I . -O3 -lz -o xbt_make_merkle_tree *.cpp ../misc/*.cpp && strip xbt_make_merkle_tree
