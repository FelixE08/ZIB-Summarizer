@echo off
cd /d "Dateipfad deines ZIB Summarizer Ordners"
start "Herd" "Dateipfad deiner Herd.exe einfügen" 
start cmd /k "npm run dev"
start http://linkzudeinerwebsite.test/