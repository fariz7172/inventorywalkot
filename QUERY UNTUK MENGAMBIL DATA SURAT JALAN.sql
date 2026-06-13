-- QUERY UNTUK MENGAMBIL DATA SURAT JALAN BERDASARKAN NAMA BARANG DAN LOKASI
SELECT do.*, m.name 
FROM delivery_orders DO
INNER JOIN delivery_order_materials dom ON do.id = dom.delivery_order_id
INNER JOIN materials m ON dom.material_id = m.id 
WHERE m.name = 'BAK CAT' 
  AND do.lokasi = 'JL.SUNTER KMY PINTU HONDA I';


-- Query Untuk Menampilkan Data Total Keseluruhan
SELECT 
    m.name AS nama_barang, 
    SUM(dom.requested_volume) AS total_dikirim, 
    m.unit AS satuan
FROM delivery_orders DO
INNER JOIN delivery_order_materials dom ON do.id = dom.delivery_order_id
INNER JOIN materials m ON dom.material_id = m.id 
WHERE m.name = 'BAK CAT' 
  AND do.lokasi = 'JL.SUNTER KMY PINTU HONDA I'
GROUP BY m.id;

-- Query Untuk Menampilkan Data Total Keseluruhan Berdasarkan Nama Barang Dan Lokasi
SELECT 
    m.name AS nama_barang, 
    SUM(dom.requested_volume) AS total_keseluruhan, 
    m.unit AS satuan
FROM delivery_orders DO
INNER JOIN delivery_order_materials dom ON do.id = dom.delivery_order_id
INNER JOIN materials m ON dom.material_id = m.id 
WHERE do.lokasi = 'JL.SUNTER KMY PINTU HONDA I'
GROUP BY m.id, m.name, m.unit
ORDER BY m.name ASC;
