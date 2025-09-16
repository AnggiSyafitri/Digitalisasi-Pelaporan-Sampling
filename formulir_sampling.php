<?php
session_start();
// Cek sesi dan peran PPC
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Pengambilan Contoh Sampling</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2, h3 { color: #2c3e50; }
        .form-section { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"], input[type="date"], textarea, select { width: 100%; padding: 10px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        .btn { padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; font-size: 16px; }
        .btn-secondary { background-color: #3498db; }
        .btn-primary { background-color: #2ecc71; }
        .contoh-item { border: 1px dashed #3498db; padding: 20px; margin-top: 20px; border-radius: 5px; background-color: #fafdff; }
        .parameter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
        .parameter-grid label { font-weight: normal; }
    </style>
</head>
<body>

<div class="container">
    <h2>Formulir Pengambilan Contoh</h2>
    <p>Login sebagai: <b><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></b> | <a href="logout.php">Logout</a></p>

    <form action="simpan_sampling.php" method="post" enctype="multipart/form-data">

        <div class="form-section">
            <h3>Informasi Perusahaan & Kegiatan</h3>
            <label for="perusahaan">Nama Perusahaan:</label>
            <input type="text" id="perusahaan" name="perusahaan" required>

            <label for="alamat">Alamat:</label>
            <textarea id="alamat" name="alamat" required></textarea>

            <label for="tanggal">Tanggal Sampling:</label>
            <input type="date" id="tanggal" name="tanggal" required>
        </div>

        <div class="form-section">
            <h3>Data Contoh Uji</h3>
            <button type="button" class="btn btn-secondary" onclick="tambahContoh()">+ Tambah Contoh Uji</button>
            <div id="contohContainer"></div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan dan Ajukan Laporan</button>
    </form>
    <p><a href="dashboard.php">⬅ Kembali ke Dashboard</a></p>
</div>

<script>
    // --- DATABASE MINI DARI EXCEL ---
    const dataSampling = {
        "Air Limbah": {
            jenisContoh: ["Air Limbah Industri", "Air Limbah Domestik"],
            parameter: ["Total Suspensi Solid", "pH", "BOD", "COD", "Minyak dan Lemak", "Amonia", "Total Coliform", "Debit", "Warna", "Sulfat", "Logam Berat", "Nitrat"],
            prosedur: ["SNI 06-6989.3-2019 (TSS)", "SNI 6989.11:2019 (pH)", "SNI 6989.72:2009 (BOD)", "SNI 6989.2:2019 (COD)"]
        },
        "Air Permukaan": {
            jenisContoh: ["Air Sungai", "Air Danau", "Air Waduk", "Air Kolam", "Air Parit", "Air Irigasi"],
            parameter: ["Temperatur", "TDS", "TSS", "pH", "BOD", "COD", "DO", "Total Fosfat", "Nitrat", "Sianida", "Fenol"],
            prosedur: ["SNI 6989.57:2008 (Metode Pengambilan Contoh Air Permukaan)", "SNI 06-6989.23-2005 (Temperatur)"]
        },
        "Air Tanah": {
            jenisContoh: ["Air Sumur Bor", "Air Sumur Gali", "Sumur Artesis", "Sumber Mata Air"],
            parameter: ["Warna", "Bau", "TDS", "Kekeruhan", "Zat Organik", "Besi", "Mangan", "Nitat", "Nitrit", "Klorida"],
            prosedur: ["SNI 6989.58:2008 (Metode Pengambilan Contoh Air Tanah)"]
        },
        "Udara": {
            jenisContoh: ["Udara Emisi dari sumber bergerak", "Udara Emisi dari sumber tidak bergerak", "Udara Ambien"],
            parameter: ["Partikulat (TSP, PM10, PM2.5)", "Sulfur dioksida (SO2)", "Karbon monoksida (CO)", "Nitrogen dioksida (NO2)", "Oksidan (O3)", "Hidrokarbon (HC)"],
            prosedur: ["SNI 7119.3:2017 (SO2)", "SNI 7119.10:2011 (CO)", "SNI 7119.2:2017 (NO2)"]
        },
        "Tingkat Kebisingan": {
            jenisContoh: null, // Jenis contoh tidak ada
            parameter: ["Kebisingan Lingkungan", "Kebisingan Lingkungan Kerja"],
            prosedur: ["SNI 8427:2017 (Pengukuran Tingkat Kebisingan Lingkungan)"]
        },
        "Tingkat Getaran": {
            jenisContoh: null, // Jenis contoh tidak ada
            parameter: ["Getaran Lingkungan untuk Kenyamanan dan Kesehatan", "Getaran Mekanik dan Kejut"],
            prosedur: ["KepMenLH No. 49 Tahun 1996 (Pengukuran Tingkat Getaran)"]
        }
    };

    let contohCounter = 0;

    function tambahContoh() {
        const container = document.getElementById("contohContainer");
        const div = document.createElement("div");
        div.className = "contoh-item";
        div.setAttribute('data-id', contohCounter);

        const namaContohOptions = Object.keys(dataSampling).map(key => `<option value="${key}">${key}</option>`).join('');

        div.innerHTML = `
            <h4>Contoh Uji #${contohCounter + 1}</h4>
            <input type="hidden" name="contoh[${contohCounter}][tipe_laporan]" id="tipe_laporan_${contohCounter}" value="">

            <label for="nama_contoh_${contohCounter}">1. Nama Contoh (Bahan):</label>
            <select name="contoh[${contohCounter}][nama_contoh]" id="nama_contoh_${contohCounter}" onchange="updateDynamicFields(${contohCounter})" required>
                <option value="">-- Pilih Bahan --</option>
                ${namaContohOptions}
            </select>

            <div id="jenis_contoh_wrapper_${contohCounter}">
                <label for="jenis_contoh_${contohCounter}">2. Jenis Contoh (Produk Uji):</label>
                <select name="contoh[${contohCounter}][jenis_contoh]" id="jenis_contoh_${contohCounter}" required>
                    <option value="">-- Pilih Nama Contoh dulu --</option>
                </select>
            </div>

            <label for="parameter_${contohCounter}">3. Parameter Uji:</label>
            <div class="parameter-grid" id="parameter_container_${contohCounter}">
                <p>-- Pilih Nama Contoh dulu --</p>
            </div>

            <label for="prosedur_${contohCounter}">4. Prosedur Pengambilan Contoh:</label>
            <select name="contoh[${contohCounter}][prosedur]" id="prosedur_${contohCounter}" required>
                <option value="">-- Pilih Nama Contoh dulu --</option>
            </select>
            
            <label for="baku_mutu_${contohCounter}">5. Baku Mutu:</label>
            <select name="contoh[${contohCounter}][baku_mutu]" id="baku_mutu_${contohCounter}">
                <option value="">-- Pilih Baku Mutu --</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran I">PP RI No. 22 Tahun 2021, Lampiran I</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran III">PP RI No. 22 Tahun 2021, Lampiran III</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran IV">PP RI No. 22 Tahun 2021, Lampiran IV</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran VII">PP RI No. 22 Tahun 2021, Lampiran VII</option>
                <option value="PERMENLH No. 05 Tahun 2014 Lampiran III">PERMENLH No. 05 Tahun 2014 Lampiran III</option>
                <option value="PERMEN LHK No. 14 Tahun 2020">Permen LHK No. 14 Tahun 2020</option>
                <option value="Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996">Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996</option>
                <option value="Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996">Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996</option>                
                <option value="Lainnya">Lainnya...</option>
            </select>
            <input type="text" name="contoh[${contohCounter}][baku_mutu_lainnya]" id="baku_mutu_lainnya_${contohCounter}" style="display:none;" placeholder="Masukkan baku mutu lainnya">

            <label>Etiket / Merek:</label>
            <input type="text" name="contoh[${contohCounter}][merek]">
            
            <label>Kode:</label>
            <input type="text" name="contoh[${contohCounter}][kode]">
            
            <label>Catatan Contoh:</label>
            <textarea name="contoh[${contohCounter}][catatan]" rows="2"></textarea>
        `;
        container.appendChild(div);
        
        // Listener untuk baku mutu "Lainnya..."
        document.getElementById(`baku_mutu_${contohCounter}`).addEventListener('change', function() {
            const lainnyaInput = document.getElementById(`baku_mutu_lainnya_${this.id.split('_')[2]}`);
            lainnyaInput.style.display = (this.value === 'Lainnya') ? 'block' : 'none';
        });

        contohCounter++;
    }

    function updateDynamicFields(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];

        const jenisContohWrapper = document.getElementById(`jenis_contoh_wrapper_${id}`);
        const jenisContohSelect = document.getElementById(`jenis_contoh_${id}`);
        const parameterContainer = document.getElementById(`parameter_container_${id}`);
        const prosedurSelect = document.getElementById(`prosedur_${id}`);
        const tipeLaporanInput = document.getElementById(`tipe_laporan_${id}`);

        // Set Tipe Laporan (air/udara)
        tipeLaporanInput.value = (selectedNamaContoh.includes("Udara") || selectedNamaContoh.includes("Kebisingan") || selectedNamaContoh.includes("Getaran")) ? 'udara' : 'air';

        // Update Jenis Contoh
        if (data && data.jenisContoh) {
            jenisContohWrapper.style.display = 'block';
            jenisContohSelect.disabled = false;
            jenisContohSelect.innerHTML = '<option value="">-- Pilih Jenis --</option>' + data.jenisContoh.map(jc => `<option value="${jc}">${jc}</option>`).join('');
        } else {
            jenisContohWrapper.style.display = 'none'; // Sembunyikan jika tidak ada
            jenisContohSelect.disabled = true;
            jenisContohSelect.innerHTML = '<option value="N/A">N/A</option>';
        }

        // Update Parameter
        if (data && data.parameter) {
            parameterContainer.innerHTML = data.parameter.map(p => 
                `<label><input type="checkbox" name="contoh[${id}][parameter][]" value="${p}"> ${p}</label>`
            ).join('');
        } else {
            parameterContainer.innerHTML = '<p>-- Pilih Nama Contoh dulu --</p>';
        }
        
        // Update Prosedur
        if (data && data.prosedur) {
            prosedurSelect.innerHTML = '<option value="">-- Pilih Prosedur --</option>' + data.prosedur.map(p => `<option value="${p}">${p}</option>`).join('');
        } else {
            prosedurSelect.innerHTML = '<option value="">-- Pilih Nama Contoh dulu --</option>';
        }
    }
</script>

</body>
</html>