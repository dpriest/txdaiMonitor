import requests, bs4, re, MySQLdb, time, datetime, sys

root_url = 'http://txdai.com'
list_url = root_url + '/zhongchou/NewHouseList/Index.html'
db = MySQLdb.connect("localhost","root","online","tool" )
cursor = db.cursor()

def get_detail_page_urls():
	response = requests.get(list_url)
	soup = bs4.BeautifulSoup(response.text)
	regex = re.compile(u'.*\u6295\u8d44.*')
	return [a.attrs.get('href') for a in soup.select('div.tz a') if regex.match(a.text)]

def get_house_data(detail_page_url):
    house_data = {}
    response = requests.get(root_url + detail_page_url)
    soup = bs4.BeautifulSoup(response.text)
    house_data['title'] = soup.select('p.lpname')[0].get_text()
    price_ange = soup.select('div.zctzfw em')[0].get_text()
    pieces = price_ange.encode('utf-8').split('\xe2\x80\x94')
    house_data['min_price'] = int(float(re.sub(',', '', pieces[0])) * 100)
    secondInfo	= soup.select('p#Secondzcg23')[0].get_text()
    mat = re.search('([-\d]+ [:\d]+)', secondInfo)
    if mat is None:
    	raise Exception("get second round start fail!")
    house_data['start_time'] = int(time.mktime(datetime.datetime.strptime(mat.groups()[0], '%Y-%m-%d %H:%M:%S').timetuple()))
    return house_data

def load_house_stats():
	house_page_urls = get_detail_page_urls()
	for house_page_url in house_page_urls:
		mat = re.search('/(\w+)\.', house_page_url)
		if mat is None:
			raise Exception("get error detail url!")
		house_data = get_house_data(house_page_url)
		house_data['key'] = mat.groups()[0]
		save_to_db(house_data)

def save_to_db(data):
	# Prepare SQL query to INSERT a record into the database.
	sql = "SELECT id, start_time FROM house WHERE house_key = '%s'" % (data['key'])
	try:
		# Execute the SQL command
		cursor.execute(sql)
		results = cursor.fetchone()
		if results:
			if results[1] == data['start_time']:
				return
			sql = "UPDATE house SET start_time = '%d', calendar_saved = 0 WHERE id = %d" % \
				(data['start_time'], results[0])
		else:
			sql = "INSERT INTO house(house_key, title, min_price, start_time, create_time, update_time) \
				VALUES ('%s', '%s', '%d', '%d', '%d', '%d' )" % \
				(data['key'], data['title'], data['min_price'], data['start_time'], time.time(), time.time())
		cursor.execute(sql)
		# Commit your changes in the database
		db.commit()
	except:
		# Rollback in case there is any error
		print "Unexpected error:", sys.exc_info()
		db.rollback()
		exit()

load_house_stats()
# disconnect from server
db.close()