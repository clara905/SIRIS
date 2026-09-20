fetch('http://172.20.10.9:8000/api/items')
  .then(response => response.json())
  .then(result => {
    console.log(result.data); // Ini adalah array barang kamu
    // Tinggal di-loop/tampilkan ke halaman web teman kamu
  })
  .catch(error => console.error('Gagal mengambil data:', error));