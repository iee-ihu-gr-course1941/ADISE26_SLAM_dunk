import * as THREE from 'three';
console.log(THREE);

export function createBoardBlocks(){
  const blocks = new THREE.Group();
  let i = 0;
  let j = 0;
  for(i=0;i<5;i++){
    for(j=0;j<5;j++){
        const blockGeometry = new THREE.BoxGeometry(1,1,1);
        const blockMaterial = new THREE.MeshStandardMaterial({
            visible: true,
            color: "#BDBDBD",
        });
        const block = new THREE.Mesh(blockGeometry, blockMaterial);
        block.position.set(i,-0.55,j);
        block.receiveShadow = true;
        blocks.add(block);
    }
  }
  return blocks;
}
