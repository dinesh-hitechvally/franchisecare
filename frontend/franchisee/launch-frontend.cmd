@echo off
cd /d D:\laragon\www\franchisecare\frontend\franchisee
"D:\laragon\bin\nodejs\node-v22\npm.cmd" run dev -- --host 127.0.0.1 --port 5173 >> vite.out.log 2>> vite.err.log
