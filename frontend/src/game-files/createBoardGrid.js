import * as THREE from 'three';
console.log(THREE);

export function createGrid(scene){
  let i = 0;
  let j = 0;
  for(i = 0;i < 5; i++){
    for(j = 0;j < 5; j++){
      const pane = new THREE.GridHelper(1,1);
      pane.position.set(i,0,j);
      pane.userData = {ground: true, x:i , z:j};
      scene.add(pane);
    }
  }
}
