
// helper: ubah angka jadi string 2 digit (5 ➜ "05", 12 ➜ "12")
const pad2 = n => n.toString().padStart(2, '0');

// nilai default
const y = 1;    // otomatis jadi "01"
const z = 6;    // otomatis jadi "06"

// ---------- data & helper ----------
const parent_z = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

const child_z = [
  [0, 18, 26, 34, 42, 59, 67, 75, 83, 91],      // 00
  [1, 19, 27, 35, 43, 50, 60, 68, 76, 84, 92],  // 01
  [2, 10, 28, 36, 44, 51, 69, 77, 85, 93],      // 02
  [3, 11, 29, 37, 45, 52, 60, 78, 86, 94],      // 03
  [4, 12, 20, 38, 46, 53, 61, 79, 87, 95],      // 04
  [5, 13, 21, 39, 47, 54, 62, 70, 88, 96],      // 05
  [6, 14, 22, 30, 48, 55, 63, 71, 89, 97],      // 06
  [7, 15, 23, 31, 49, 56, 64, 72, 80, 98],      // 07
  [8, 16, 24, 32, 40, 57, 65, 73, 81, 99],      // 08
  [9, 17, 25, 33, 41, 58, 66, 74, 82, 90],      // 09
];

// const template_number = Number(`11017${pad2(x)}${pad2(y)}${pad2(z)}`);

const x = 43;                       // x sesi ini
const targetParent = 8;             // parent yang dicari
const targetChildren = [40, 65, 81];// child yang dicari di baris parent 8

//---------------------------------- loop  (y → parent → child)
for (let y = 1; y <= 99; y++) {
  console.log(`\n=========== y = ${pad2(y)} ===========`);

  for (let pIdx = 0; pIdx < parent_z.length; pIdx++) {
    const parent = parent_z[pIdx];


    // cuma masuk child loop kalau parent match
    if (parent === targetParent) {
        console.log(`-- parent_z ${parent} (MATCH)`);
      for (const z of child_z[pIdx]) {
        const found = targetChildren.includes(z);
        const templateNum = Number(
          `11017${pad2(x)}${pad2(y)}${pad2(z)}`
        );

        if (found) {
          console.log(
            `   ✓ child_z ${pad2(z)}  →  ${templateNum}  (FOUND)`
          );
        } else {
          console.log(
            `   ✗ child_z ${pad2(z)}  →  ${templateNum}  (not found)`
          );
        }
      }
    }else{
        console.log(`-- parent_z ${parent} (skip)`);
    }
  }
}
  