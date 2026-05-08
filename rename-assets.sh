#!/bin/bash
BASE="/home/nika/projects/racine-backend/assset racine by ganda"

rename_folder() {
  local folder="$1"
  local prefix="$2"
  local i=1
  for f in "$folder"/*.jpeg "$folder"/*.jpg "$folder"/*.png; do
    [ -f "$f" ] || continue
    ext="${f##*.}"
    mv "$f" "$folder/${prefix}-$(printf '%02d' $i).${ext}"
    i=$((i+1))
  done
}

rename_folder "$BASE/hero acceuils" "hero"
rename_folder "$BASE/Bombers" "bomber"
rename_folder "$BASE/vestes" "veste"
rename_folder "$BASE/chemise" "chemise"
rename_folder "$BASE/kimono" "kimono"
rename_folder "$BASE/soie" "soie"
rename_folder "$BASE/traditionnelle" "traditionnel"
rename_folder "$BASE/tenue soirré" "soiree"
rename_folder "$BASE/bazin" "bazin"
rename_folder "$BASE/Pagnes" "pagne"
rename_folder "$BASE/accessoires/Kit de voyage Bleue" "kit-voyage-bleu"
rename_folder "$BASE/accessoires/Kit de voyage Noir" "kit-voyage-noir"
rename_folder "$BASE/accessoires/Kit de voyage Rouge" "kit-voyage-rouge"

echo "Renommage terminé."
