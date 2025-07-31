#!/bin/bash

cd accounts

for name in *
do
    if [ -f "$name" ]; then
        ./$name
    fi
done
