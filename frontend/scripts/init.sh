#!/bin/bash

app/console doctrine:migrations:migrate
app/console cache:clear
