#!/usr/bin/env python

import sys

# get pyyaml working: ugly
sys.path.append("/home/impro/usr/local/lib/python2.5/site-packages/")

import yaml
import pprint

config_file = "mlupdater.yml"

pp = pprint.PrettyPrinter(indent=4)
try:
	config = yaml.load(file(config_file,'r'))
	#print(pp.pprint(config))
	print("[OK] file is valid.")
	sys.exit(0)
except yaml.YAMLError, exc:
	print("[ERROR] invalid file.")
	print exc
	sys.exit(1)
