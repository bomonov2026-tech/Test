/* TartibOn — SHASHKA 3D poyga moduli. Manba: shashka-traffic-source (React'siz).
   three.js CDN'dan yuklanadi, qolgani shu fayl ichida. */
let LOADED=null;
export function shLoad(){ if(LOADED) return LOADED; LOADED=(async()=>{
let THREE;
try{ THREE=await import('https://cdn.jsdelivr.net/npm/three@0.181.0/build/three.module.js'); }
catch(e){ THREE=await import('https://unpkg.com/three@0.181.0/build/three.module.js'); }
/* three.js qo'shimchalari */
let __mergeGeometries;
{
const {
	BufferAttribute,
	BufferGeometry,
	Float32BufferAttribute,
	InstancedBufferAttribute,
	InterleavedBuffer,
	InterleavedBufferAttribute,
	TriangleFanDrawMode,
	TriangleStripDrawMode,
	TrianglesDrawMode,
	Vector3,
} = THREE;

/**
 * @module BufferGeometryUtils
 * @three_import import * as BufferGeometryUtils from 'three/addons/utils/BufferGeometryUtils.js';
 */

/**
 * Computes vertex tangents using the MikkTSpace algorithm. MikkTSpace generates the same tangents consistently,
 * and is used in most modelling tools and normal map bakers. Use MikkTSpace for materials with normal maps,
 * because inconsistent tangents may lead to subtle visual issues in the normal map, particularly around mirrored
 * UV seams.
 *
 * In comparison to this method, {@link BufferGeometry#computeTangents} (a custom algorithm) generates tangents that
 * probably will not match the tangents in other software. The custom algorithm is sufficient for general use with a
 * custom material, and may be faster than MikkTSpace.
 *
 * Returns the original BufferGeometry. Indexed geometries will be de-indexed. Requires position, normal, and uv attributes.
 *
 * @param {BufferGeometry} geometry - The geometry to compute tangents for.
 * @param {Object} MikkTSpace - Instance of `examples/jsm/libs/mikktspace.module.js`, or `mikktspace` npm package.
 * Await `MikkTSpace.ready` before use.
 * @param {boolean} [negateSign=true] - Whether to negate the sign component (.w) of each tangent.
 * Required for normal map conventions in some formats, including glTF.
 * @return {BufferGeometry} The updated geometry.
 */
function computeMikkTSpaceTangents( geometry, MikkTSpace, negateSign = true ) {

	if ( ! MikkTSpace || ! MikkTSpace.isReady ) {

		throw new Error( 'BufferGeometryUtils: Initialized MikkTSpace library required.' );

	}

	if ( ! geometry.hasAttribute( 'position' ) || ! geometry.hasAttribute( 'normal' ) || ! geometry.hasAttribute( 'uv' ) ) {

		throw new Error( 'BufferGeometryUtils: Tangents require "position", "normal", and "uv" attributes.' );

	}

	function getAttributeArray( attribute ) {

		if ( attribute.normalized || attribute.isInterleavedBufferAttribute ) {

			const dstArray = new Float32Array( attribute.count * attribute.itemSize );

			for ( let i = 0, j = 0; i < attribute.count; i ++ ) {

				dstArray[ j ++ ] = attribute.getX( i );
				dstArray[ j ++ ] = attribute.getY( i );

				if ( attribute.itemSize > 2 ) {

					dstArray[ j ++ ] = attribute.getZ( i );

				}

			}

			return dstArray;

		}

		if ( attribute.array instanceof Float32Array ) {

			return attribute.array;

		}

		return new Float32Array( attribute.array );

	}

	// MikkTSpace algorithm requires non-indexed input.

	const _geometry = geometry.index ? geometry.toNonIndexed() : geometry;

	// Compute vertex tangents.

	const tangents = MikkTSpace.generateTangents(

		getAttributeArray( _geometry.attributes.position ),
		getAttributeArray( _geometry.attributes.normal ),
		getAttributeArray( _geometry.attributes.uv )

	);

	// Texture coordinate convention of glTF differs from the apparent
	// default of the MikkTSpace library; .w component must be flipped.

	if ( negateSign ) {

		for ( let i = 3; i < tangents.length; i += 4 ) {

			tangents[ i ] *= - 1;

		}

	}

	//

	_geometry.setAttribute( 'tangent', new BufferAttribute( tangents, 4 ) );

	if ( geometry !== _geometry ) {

		geometry.copy( _geometry );

	}

	return geometry;

}

/**
 * Merges a set of geometries into a single instance. All geometries must have compatible attributes.
 *
 * @param {Array<BufferGeometry>} geometries - The geometries to merge.
 * @param {boolean} [useGroups=false] - Whether to use groups or not.
 * @return {?BufferGeometry} The merged geometry. Returns `null` if the merge does not succeed.
 */
function mergeGeometries( geometries, useGroups = false ) {

	const isIndexed = geometries[ 0 ].index !== null;

	const attributesUsed = new Set( Object.keys( geometries[ 0 ].attributes ) );
	const morphAttributesUsed = new Set( Object.keys( geometries[ 0 ].morphAttributes ) );

	const attributes = {};
	const morphAttributes = {};

	const morphTargetsRelative = geometries[ 0 ].morphTargetsRelative;

	const mergedGeometry = new BufferGeometry();

	let offset = 0;

	for ( let i = 0; i < geometries.length; ++ i ) {

		const geometry = geometries[ i ];
		let attributesCount = 0;

		// ensure that all geometries are indexed, or none

		if ( isIndexed !== ( geometry.index !== null ) ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index ' + i + '. All geometries must have compatible attributes; make sure index attribute exists among all geometries, or in none of them.' );
			return null;

		}

		// gather attributes, exit early if they're different

		for ( const name in geometry.attributes ) {

			if ( ! attributesUsed.has( name ) ) {

				console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index ' + i + '. All geometries must have compatible attributes; make sure "' + name + '" attribute exists among all geometries, or in none of them.' );
				return null;

			}

			if ( attributes[ name ] === undefined ) attributes[ name ] = [];

			attributes[ name ].push( geometry.attributes[ name ] );

			attributesCount ++;

		}

		// ensure geometries have the same number of attributes

		if ( attributesCount !== attributesUsed.size ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index ' + i + '. Make sure all geometries have the same number of attributes.' );
			return null;

		}

		// gather morph attributes, exit early if they're different

		if ( morphTargetsRelative !== geometry.morphTargetsRelative ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index ' + i + '. .morphTargetsRelative must be consistent throughout all geometries.' );
			return null;

		}

		for ( const name in geometry.morphAttributes ) {

			if ( ! morphAttributesUsed.has( name ) ) {

				console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index ' + i + '.  .morphAttributes must be consistent throughout all geometries.' );
				return null;

			}

			if ( morphAttributes[ name ] === undefined ) morphAttributes[ name ] = [];

			morphAttributes[ name ].push( geometry.morphAttributes[ name ] );

		}

		if ( useGroups ) {

			let count;

			if ( isIndexed ) {

				count = geometry.index.count;

			} else if ( geometry.attributes.position !== undefined ) {

				count = geometry.attributes.position.count;

			} else {

				console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index ' + i + '. The geometry must have either an index or a position attribute' );
				return null;

			}

			mergedGeometry.addGroup( offset, count, i );

			offset += count;

		}

	}

	// merge indices

	if ( isIndexed ) {

		let indexOffset = 0;
		const mergedIndex = [];

		for ( let i = 0; i < geometries.length; ++ i ) {

			const index = geometries[ i ].index;

			for ( let j = 0; j < index.count; ++ j ) {

				mergedIndex.push( index.getX( j ) + indexOffset );

			}

			indexOffset += geometries[ i ].attributes.position.count;

		}

		mergedGeometry.setIndex( mergedIndex );

	}

	// merge attributes

	for ( const name in attributes ) {

		const mergedAttribute = mergeAttributes( attributes[ name ] );

		if ( ! mergedAttribute ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the ' + name + ' attribute.' );
			return null;

		}

		mergedGeometry.setAttribute( name, mergedAttribute );

	}

	// merge morph attributes

	for ( const name in morphAttributes ) {

		const numMorphTargets = morphAttributes[ name ][ 0 ].length;

		if ( numMorphTargets === 0 ) break;

		mergedGeometry.morphAttributes = mergedGeometry.morphAttributes || {};
		mergedGeometry.morphAttributes[ name ] = [];

		for ( let i = 0; i < numMorphTargets; ++ i ) {

			const morphAttributesToMerge = [];

			for ( let j = 0; j < morphAttributes[ name ].length; ++ j ) {

				morphAttributesToMerge.push( morphAttributes[ name ][ j ][ i ] );

			}

			const mergedMorphAttribute = mergeAttributes( morphAttributesToMerge );

			if ( ! mergedMorphAttribute ) {

				console.error( 'THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the ' + name + ' morphAttribute.' );
				return null;

			}

			mergedGeometry.morphAttributes[ name ].push( mergedMorphAttribute );

		}

	}

	return mergedGeometry;

}

/**
 * Merges a set of attributes into a single instance. All attributes must have compatible properties and types.
 * Instances of {@link InterleavedBufferAttribute} are not supported.
 *
 * @param {Array<BufferAttribute>} attributes - The attributes to merge.
 * @return {?BufferAttribute} The merged attribute. Returns `null` if the merge does not succeed.
 */
function mergeAttributes( attributes ) {

	let TypedArray;
	let itemSize;
	let normalized;
	let gpuType = - 1;
	let arrayLength = 0;

	for ( let i = 0; i < attributes.length; ++ i ) {

		const attribute = attributes[ i ];

		if ( TypedArray === undefined ) TypedArray = attribute.array.constructor;
		if ( TypedArray !== attribute.array.constructor ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.array must be of consistent array types across matching attributes.' );
			return null;

		}

		if ( itemSize === undefined ) itemSize = attribute.itemSize;
		if ( itemSize !== attribute.itemSize ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.itemSize must be consistent across matching attributes.' );
			return null;

		}

		if ( normalized === undefined ) normalized = attribute.normalized;
		if ( normalized !== attribute.normalized ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.normalized must be consistent across matching attributes.' );
			return null;

		}

		if ( gpuType === - 1 ) gpuType = attribute.gpuType;
		if ( gpuType !== attribute.gpuType ) {

			console.error( 'THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.gpuType must be consistent across matching attributes.' );
			return null;

		}

		arrayLength += attribute.count * itemSize;

	}

	const array = new TypedArray( arrayLength );
	const result = new BufferAttribute( array, itemSize, normalized );
	let offset = 0;

	for ( let i = 0; i < attributes.length; ++ i ) {

		const attribute = attributes[ i ];
		if ( attribute.isInterleavedBufferAttribute ) {

			const tupleOffset = offset / itemSize;
			for ( let j = 0, l = attribute.count; j < l; j ++ ) {

				for ( let c = 0; c < itemSize; c ++ ) {

					const value = attribute.getComponent( j, c );
					result.setComponent( j + tupleOffset, c, value );

				}

			}

		} else {

			array.set( attribute.array, offset );

		}

		offset += attribute.count * itemSize;

	}

	if ( gpuType !== undefined ) {

		result.gpuType = gpuType;

	}

	return result;

}

/**
 * Performs a deep clone of the given buffer attribute.
 *
 * @param {BufferAttribute} attribute - The attribute to clone.
 * @return {BufferAttribute} The cloned attribute.
 */
function deepCloneAttribute( attribute ) {

	if ( attribute.isInstancedInterleavedBufferAttribute || attribute.isInterleavedBufferAttribute ) {

		return deinterleaveAttribute( attribute );

	}

	if ( attribute.isInstancedBufferAttribute ) {

		return new InstancedBufferAttribute().copy( attribute );

	}

	return new BufferAttribute().copy( attribute );

}

/**
 * Interleaves a set of attributes and returns a new array of corresponding attributes that share a
 * single {@link InterleavedBuffer} instance. All attributes must have compatible types.
 *
 * @param {Array<BufferAttribute>} attributes - The attributes to interleave.
 * @return {?Array<InterleavedBufferAttribute>} An array of interleaved attributes. If interleave does not succeed, the method returns `null`.
 */
function interleaveAttributes( attributes ) {

	// Interleaves the provided attributes into an InterleavedBuffer and returns
	// a set of InterleavedBufferAttributes for each attribute
	let TypedArray;
	let arrayLength = 0;
	let stride = 0;

	// calculate the length and type of the interleavedBuffer
	for ( let i = 0, l = attributes.length; i < l; ++ i ) {

		const attribute = attributes[ i ];

		if ( TypedArray === undefined ) TypedArray = attribute.array.constructor;
		if ( TypedArray !== attribute.array.constructor ) {

			console.error( 'AttributeBuffers of different types cannot be interleaved' );
			return null;

		}

		arrayLength += attribute.array.length;
		stride += attribute.itemSize;

	}

	// Create the set of buffer attributes
	const interleavedBuffer = new InterleavedBuffer( new TypedArray( arrayLength ), stride );
	let offset = 0;
	const res = [];
	const getters = [ 'getX', 'getY', 'getZ', 'getW' ];
	const setters = [ 'setX', 'setY', 'setZ', 'setW' ];

	for ( let j = 0, l = attributes.length; j < l; j ++ ) {

		const attribute = attributes[ j ];
		const itemSize = attribute.itemSize;
		const count = attribute.count;
		const iba = new InterleavedBufferAttribute( interleavedBuffer, itemSize, offset, attribute.normalized );
		res.push( iba );

		offset += itemSize;

		// Move the data for each attribute into the new interleavedBuffer
		// at the appropriate offset
		for ( let c = 0; c < count; c ++ ) {

			for ( let k = 0; k < itemSize; k ++ ) {

				iba[ setters[ k ] ]( c, attribute[ getters[ k ] ]( c ) );

			}

		}

	}

	return res;

}

/**
 * Returns a new, non-interleaved version of the given attribute.
 *
 * @param {InterleavedBufferAttribute} attribute - The interleaved attribute.
 * @return {BufferAttribute} The non-interleaved attribute.
 */
function deinterleaveAttribute( attribute ) {

	const cons = attribute.data.array.constructor;
	const count = attribute.count;
	const itemSize = attribute.itemSize;
	const normalized = attribute.normalized;

	const array = new cons( count * itemSize );
	let newAttribute;
	if ( attribute.isInstancedInterleavedBufferAttribute ) {

		newAttribute = new InstancedBufferAttribute( array, itemSize, normalized, attribute.meshPerAttribute );

	} else {

		newAttribute = new BufferAttribute( array, itemSize, normalized );

	}

	for ( let i = 0; i < count; i ++ ) {

		newAttribute.setX( i, attribute.getX( i ) );

		if ( itemSize >= 2 ) {

			newAttribute.setY( i, attribute.getY( i ) );

		}

		if ( itemSize >= 3 ) {

			newAttribute.setZ( i, attribute.getZ( i ) );

		}

		if ( itemSize >= 4 ) {

			newAttribute.setW( i, attribute.getW( i ) );

		}

	}

	return newAttribute;

}

/**
 * Deinterleaves all attributes on the given geometry.
 *
 * @param {BufferGeometry} geometry - The geometry to deinterleave.
 */
function deinterleaveGeometry( geometry ) {

	const attributes = geometry.attributes;
	const morphTargets = geometry.morphTargets;
	const attrMap = new Map();

	for ( const key in attributes ) {

		const attr = attributes[ key ];
		if ( attr.isInterleavedBufferAttribute ) {

			if ( ! attrMap.has( attr ) ) {

				attrMap.set( attr, deinterleaveAttribute( attr ) );

			}

			attributes[ key ] = attrMap.get( attr );

		}

	}

	for ( const key in morphTargets ) {

		const attr = morphTargets[ key ];
		if ( attr.isInterleavedBufferAttribute ) {

			if ( ! attrMap.has( attr ) ) {

				attrMap.set( attr, deinterleaveAttribute( attr ) );

			}

			morphTargets[ key ] = attrMap.get( attr );

		}

	}

}

/**
 * Returns the amount of bytes used by all attributes to represent the geometry.
 *
 * @param {BufferGeometry} geometry - The geometry.
 * @return {number} The estimate bytes used.
 */
function estimateBytesUsed( geometry ) {

	// Return the estimated memory used by this geometry in bytes
	// Calculate using itemSize, count, and BYTES_PER_ELEMENT to account
	// for InterleavedBufferAttributes.
	let mem = 0;
	for ( const name in geometry.attributes ) {

		const attr = geometry.getAttribute( name );
		mem += attr.count * attr.itemSize * attr.array.BYTES_PER_ELEMENT;

	}

	const indices = geometry.getIndex();
	mem += indices ? indices.count * indices.itemSize * indices.array.BYTES_PER_ELEMENT : 0;
	return mem;

}

/**
 * Returns a new geometry with vertices for which all similar vertex attributes (within tolerance) are merged.
 *
 * @param {BufferGeometry} geometry - The geometry to merge vertices for.
 * @param {number} [tolerance=1e-4] - The tolerance value.
 * @return {BufferGeometry} - The new geometry with merged vertices.
 */
function mergeVertices( geometry, tolerance = 1e-4 ) {

	tolerance = Math.max( tolerance, Number.EPSILON );

	// Generate an index buffer if the geometry doesn't have one, or optimize it
	// if it's already available.
	const hashToIndex = {};
	const indices = geometry.getIndex();
	const positions = geometry.getAttribute( 'position' );
	const vertexCount = indices ? indices.count : positions.count;

	// next value for triangle indices
	let nextIndex = 0;

	// attributes and new attribute arrays
	const attributeNames = Object.keys( geometry.attributes );
	const tmpAttributes = {};
	const tmpMorphAttributes = {};
	const newIndices = [];
	const getters = [ 'getX', 'getY', 'getZ', 'getW' ];
	const setters = [ 'setX', 'setY', 'setZ', 'setW' ];

	// Initialize the arrays, allocating space conservatively. Extra
	// space will be trimmed in the last step.
	for ( let i = 0, l = attributeNames.length; i < l; i ++ ) {

		const name = attributeNames[ i ];
		const attr = geometry.attributes[ name ];

		tmpAttributes[ name ] = new attr.constructor(
			new attr.array.constructor( attr.count * attr.itemSize ),
			attr.itemSize,
			attr.normalized
		);

		const morphAttributes = geometry.morphAttributes[ name ];
		if ( morphAttributes ) {

			if ( ! tmpMorphAttributes[ name ] ) tmpMorphAttributes[ name ] = [];
			morphAttributes.forEach( ( morphAttr, i ) => {

				const array = new morphAttr.array.constructor( morphAttr.count * morphAttr.itemSize );
				tmpMorphAttributes[ name ][ i ] = new morphAttr.constructor( array, morphAttr.itemSize, morphAttr.normalized );

			} );

		}

	}

	// convert the error tolerance to an amount of decimal places to truncate to
	const halfTolerance = tolerance * 0.5;
	const exponent = Math.log10( 1 / tolerance );
	const hashMultiplier = Math.pow( 10, exponent );
	const hashAdditive = halfTolerance * hashMultiplier;
	for ( let i = 0; i < vertexCount; i ++ ) {

		const index = indices ? indices.getX( i ) : i;

		// Generate a hash for the vertex attributes at the current index 'i'
		let hash = '';
		for ( let j = 0, l = attributeNames.length; j < l; j ++ ) {

			const name = attributeNames[ j ];
			const attribute = geometry.getAttribute( name );
			const itemSize = attribute.itemSize;

			for ( let k = 0; k < itemSize; k ++ ) {

				// double tilde truncates the decimal value
				hash += `${ ~ ~ ( attribute[ getters[ k ] ]( index ) * hashMultiplier + hashAdditive ) },`;

			}

		}

		// Add another reference to the vertex if it's already
		// used by another index
		if ( hash in hashToIndex ) {

			newIndices.push( hashToIndex[ hash ] );

		} else {

			// copy data to the new index in the temporary attributes
			for ( let j = 0, l = attributeNames.length; j < l; j ++ ) {

				const name = attributeNames[ j ];
				const attribute = geometry.getAttribute( name );
				const morphAttributes = geometry.morphAttributes[ name ];
				const itemSize = attribute.itemSize;
				const newArray = tmpAttributes[ name ];
				const newMorphArrays = tmpMorphAttributes[ name ];

				for ( let k = 0; k < itemSize; k ++ ) {

					const getterFunc = getters[ k ];
					const setterFunc = setters[ k ];
					newArray[ setterFunc ]( nextIndex, attribute[ getterFunc ]( index ) );

					if ( morphAttributes ) {

						for ( let m = 0, ml = morphAttributes.length; m < ml; m ++ ) {

							newMorphArrays[ m ][ setterFunc ]( nextIndex, morphAttributes[ m ][ getterFunc ]( index ) );

						}

					}

				}

			}

			hashToIndex[ hash ] = nextIndex;
			newIndices.push( nextIndex );
			nextIndex ++;

		}

	}

	// generate result BufferGeometry
	const result = geometry.clone();
	for ( const name in geometry.attributes ) {

		const tmpAttribute = tmpAttributes[ name ];

		result.setAttribute( name, new tmpAttribute.constructor(
			tmpAttribute.array.slice( 0, nextIndex * tmpAttribute.itemSize ),
			tmpAttribute.itemSize,
			tmpAttribute.normalized,
		) );

		if ( ! ( name in tmpMorphAttributes ) ) continue;

		for ( let j = 0; j < tmpMorphAttributes[ name ].length; j ++ ) {

			const tmpMorphAttribute = tmpMorphAttributes[ name ][ j ];

			result.morphAttributes[ name ][ j ] = new tmpMorphAttribute.constructor(
				tmpMorphAttribute.array.slice( 0, nextIndex * tmpMorphAttribute.itemSize ),
				tmpMorphAttribute.itemSize,
				tmpMorphAttribute.normalized,
			);

		}

	}

	// indices

	result.setIndex( newIndices );

	return result;

}

/**
 * Returns a new indexed geometry based on `TrianglesDrawMode` draw mode.
 * This mode corresponds to the `gl.TRIANGLES` primitive in WebGL.
 *
 * @param {BufferGeometry} geometry - The geometry to convert.
 * @param {number} drawMode - The current draw mode.
 * @return {BufferGeometry} The new geometry using `TrianglesDrawMode`.
 */
function toTrianglesDrawMode( geometry, drawMode ) {

	if ( drawMode === TrianglesDrawMode ) {

		console.warn( 'THREE.BufferGeometryUtils.toTrianglesDrawMode(): Geometry already defined as triangles.' );
		return geometry;

	}

	if ( drawMode === TriangleFanDrawMode || drawMode === TriangleStripDrawMode ) {

		let index = geometry.getIndex();

		// generate index if not present

		if ( index === null ) {

			const indices = [];

			const position = geometry.getAttribute( 'position' );

			if ( position !== undefined ) {

				for ( let i = 0; i < position.count; i ++ ) {

					indices.push( i );

				}

				geometry.setIndex( indices );
				index = geometry.getIndex();

			} else {

				console.error( 'THREE.BufferGeometryUtils.toTrianglesDrawMode(): Undefined position attribute. Processing not possible.' );
				return geometry;

			}

		}

		//

		const numberOfTriangles = index.count - 2;
		const newIndices = [];

		if ( drawMode === TriangleFanDrawMode ) {

			// gl.TRIANGLE_FAN

			for ( let i = 1; i <= numberOfTriangles; i ++ ) {

				newIndices.push( index.getX( 0 ) );
				newIndices.push( index.getX( i ) );
				newIndices.push( index.getX( i + 1 ) );

			}

		} else {

			// gl.TRIANGLE_STRIP

			for ( let i = 0; i < numberOfTriangles; i ++ ) {

				if ( i % 2 === 0 ) {

					newIndices.push( index.getX( i ) );
					newIndices.push( index.getX( i + 1 ) );
					newIndices.push( index.getX( i + 2 ) );

				} else {

					newIndices.push( index.getX( i + 2 ) );
					newIndices.push( index.getX( i + 1 ) );
					newIndices.push( index.getX( i ) );

				}

			}

		}

		if ( ( newIndices.length / 3 ) !== numberOfTriangles ) {

			console.error( 'THREE.BufferGeometryUtils.toTrianglesDrawMode(): Unable to generate correct amount of triangles.' );

		}

		// build final geometry

		const newGeometry = geometry.clone();
		newGeometry.setIndex( newIndices );
		newGeometry.clearGroups();

		return newGeometry;

	} else {

		console.error( 'THREE.BufferGeometryUtils.toTrianglesDrawMode(): Unknown draw mode:', drawMode );
		return geometry;

	}

}

/**
 * Calculates the morphed attributes of a morphed/skinned BufferGeometry.
 *
 * Helpful for Raytracing or Decals (i.e. a `DecalGeometry` applied to a morphed Object with a `BufferGeometry`
 * will use the original `BufferGeometry`, not the morphed/skinned one, generating an incorrect result.
 * Using this function to create a shadow `Object3`D the `DecalGeometry` can be correctly generated).
 *
 * @param {Mesh|Line|Points} object - The 3D object to compute morph attributes for.
 * @return {Object} An object with original position/normal attributes and morphed ones.
 */
function computeMorphedAttributes( object ) {

	const _vA = new Vector3();
	const _vB = new Vector3();
	const _vC = new Vector3();

	const _tempA = new Vector3();
	const _tempB = new Vector3();
	const _tempC = new Vector3();

	const _morphA = new Vector3();
	const _morphB = new Vector3();
	const _morphC = new Vector3();

	function _calculateMorphedAttributeData(
		object,
		attribute,
		morphAttribute,
		morphTargetsRelative,
		a,
		b,
		c,
		modifiedAttributeArray
	) {

		_vA.fromBufferAttribute( attribute, a );
		_vB.fromBufferAttribute( attribute, b );
		_vC.fromBufferAttribute( attribute, c );

		const morphInfluences = object.morphTargetInfluences;

		if ( morphAttribute && morphInfluences ) {

			_morphA.set( 0, 0, 0 );
			_morphB.set( 0, 0, 0 );
			_morphC.set( 0, 0, 0 );

			for ( let i = 0, il = morphAttribute.length; i < il; i ++ ) {

				const influence = morphInfluences[ i ];
				const morph = morphAttribute[ i ];

				if ( influence === 0 ) continue;

				_tempA.fromBufferAttribute( morph, a );
				_tempB.fromBufferAttribute( morph, b );
				_tempC.fromBufferAttribute( morph, c );

				if ( morphTargetsRelative ) {

					_morphA.addScaledVector( _tempA, influence );
					_morphB.addScaledVector( _tempB, influence );
					_morphC.addScaledVector( _tempC, influence );

				} else {

					_morphA.addScaledVector( _tempA.sub( _vA ), influence );
					_morphB.addScaledVector( _tempB.sub( _vB ), influence );
					_morphC.addScaledVector( _tempC.sub( _vC ), influence );

				}

			}

			_vA.add( _morphA );
			_vB.add( _morphB );
			_vC.add( _morphC );

		}

		if ( object.isSkinnedMesh ) {

			object.applyBoneTransform( a, _vA );
			object.applyBoneTransform( b, _vB );
			object.applyBoneTransform( c, _vC );

		}

		modifiedAttributeArray[ a * 3 + 0 ] = _vA.x;
		modifiedAttributeArray[ a * 3 + 1 ] = _vA.y;
		modifiedAttributeArray[ a * 3 + 2 ] = _vA.z;
		modifiedAttributeArray[ b * 3 + 0 ] = _vB.x;
		modifiedAttributeArray[ b * 3 + 1 ] = _vB.y;
		modifiedAttributeArray[ b * 3 + 2 ] = _vB.z;
		modifiedAttributeArray[ c * 3 + 0 ] = _vC.x;
		modifiedAttributeArray[ c * 3 + 1 ] = _vC.y;
		modifiedAttributeArray[ c * 3 + 2 ] = _vC.z;

	}

	const geometry = object.geometry;
	const material = object.material;

	let a, b, c;
	const index = geometry.index;
	const positionAttribute = geometry.attributes.position;
	const morphPosition = geometry.morphAttributes.position;
	const morphTargetsRelative = geometry.morphTargetsRelative;
	const normalAttribute = geometry.attributes.normal;
	const morphNormal = geometry.morphAttributes.position;

	const groups = geometry.groups;
	const drawRange = geometry.drawRange;
	let i, j, il, jl;
	let group;
	let start, end;

	const modifiedPosition = new Float32Array( positionAttribute.count * positionAttribute.itemSize );
	const modifiedNormal = new Float32Array( normalAttribute.count * normalAttribute.itemSize );

	if ( index !== null ) {

		// indexed buffer geometry

		if ( Array.isArray( material ) ) {

			for ( i = 0, il = groups.length; i < il; i ++ ) {

				group = groups[ i ];

				start = Math.max( group.start, drawRange.start );
				end = Math.min( ( group.start + group.count ), ( drawRange.start + drawRange.count ) );

				for ( j = start, jl = end; j < jl; j += 3 ) {

					a = index.getX( j );
					b = index.getX( j + 1 );
					c = index.getX( j + 2 );

					_calculateMorphedAttributeData(
						object,
						positionAttribute,
						morphPosition,
						morphTargetsRelative,
						a, b, c,
						modifiedPosition
					);

					_calculateMorphedAttributeData(
						object,
						normalAttribute,
						morphNormal,
						morphTargetsRelative,
						a, b, c,
						modifiedNormal
					);

				}

			}

		} else {

			start = Math.max( 0, drawRange.start );
			end = Math.min( index.count, ( drawRange.start + drawRange.count ) );

			for ( i = start, il = end; i < il; i += 3 ) {

				a = index.getX( i );
				b = index.getX( i + 1 );
				c = index.getX( i + 2 );

				_calculateMorphedAttributeData(
					object,
					positionAttribute,
					morphPosition,
					morphTargetsRelative,
					a, b, c,
					modifiedPosition
				);

				_calculateMorphedAttributeData(
					object,
					normalAttribute,
					morphNormal,
					morphTargetsRelative,
					a, b, c,
					modifiedNormal
				);

			}

		}

	} else {

		// non-indexed buffer geometry

		if ( Array.isArray( material ) ) {

			for ( i = 0, il = groups.length; i < il; i ++ ) {

				group = groups[ i ];

				start = Math.max( group.start, drawRange.start );
				end = Math.min( ( group.start + group.count ), ( drawRange.start + drawRange.count ) );

				for ( j = start, jl = end; j < jl; j += 3 ) {

					a = j;
					b = j + 1;
					c = j + 2;

					_calculateMorphedAttributeData(
						object,
						positionAttribute,
						morphPosition,
						morphTargetsRelative,
						a, b, c,
						modifiedPosition
					);

					_calculateMorphedAttributeData(
						object,
						normalAttribute,
						morphNormal,
						morphTargetsRelative,
						a, b, c,
						modifiedNormal
					);

				}

			}

		} else {

			start = Math.max( 0, drawRange.start );
			end = Math.min( positionAttribute.count, ( drawRange.start + drawRange.count ) );

			for ( i = start, il = end; i < il; i += 3 ) {

				a = i;
				b = i + 1;
				c = i + 2;

				_calculateMorphedAttributeData(
					object,
					positionAttribute,
					morphPosition,
					morphTargetsRelative,
					a, b, c,
					modifiedPosition
				);

				_calculateMorphedAttributeData(
					object,
					normalAttribute,
					morphNormal,
					morphTargetsRelative,
					a, b, c,
					modifiedNormal
				);

			}

		}

	}

	const morphedPositionAttribute = new Float32BufferAttribute( modifiedPosition, 3 );
	const morphedNormalAttribute = new Float32BufferAttribute( modifiedNormal, 3 );

	return {

		positionAttribute: positionAttribute,
		normalAttribute: normalAttribute,
		morphedPositionAttribute: morphedPositionAttribute,
		morphedNormalAttribute: morphedNormalAttribute

	};

}

/**
 * Merges the {@link BufferGeometry#groups} for the given geometry.
 *
 * @param {BufferGeometry} geometry - The geometry to modify.
 * @return {BufferGeometry} - The updated geometry
 */
function mergeGroups( geometry ) {

	if ( geometry.groups.length === 0 ) {

		console.warn( 'THREE.BufferGeometryUtils.mergeGroups(): No groups are defined. Nothing to merge.' );
		return geometry;

	}

	let groups = geometry.groups;

	// sort groups by material index

	groups = groups.sort( ( a, b ) => {

		if ( a.materialIndex !== b.materialIndex ) return a.materialIndex - b.materialIndex;

		return a.start - b.start;

	} );

	// create index for non-indexed geometries

	if ( geometry.getIndex() === null ) {

		const positionAttribute = geometry.getAttribute( 'position' );
		const indices = [];

		for ( let i = 0; i < positionAttribute.count; i += 3 ) {

			indices.push( i, i + 1, i + 2 );

		}

		geometry.setIndex( indices );

	}

	// sort index

	const index = geometry.getIndex();

	const newIndices = [];

	for ( let i = 0; i < groups.length; i ++ ) {

		const group = groups[ i ];

		const groupStart = group.start;
		const groupLength = groupStart + group.count;

		for ( let j = groupStart; j < groupLength; j ++ ) {

			newIndices.push( index.getX( j ) );

		}

	}

	geometry.dispose(); // Required to force buffer recreation
	geometry.setIndex( newIndices );

	// update groups indices

	let start = 0;

	for ( let i = 0; i < groups.length; i ++ ) {

		const group = groups[ i ];

		group.start = start;
		start += group.count;

	}

	// merge groups

	let currentGroup = groups[ 0 ];

	geometry.groups = [ currentGroup ];

	for ( let i = 1; i < groups.length; i ++ ) {

		const group = groups[ i ];

		if ( currentGroup.materialIndex === group.materialIndex ) {

			currentGroup.count += group.count;

		} else {

			currentGroup = group;
			geometry.groups.push( currentGroup );

		}

	}

	return geometry;

}

/**
 * Modifies the supplied geometry if it is non-indexed, otherwise creates a new,
 * non-indexed geometry. Returns the geometry with smooth normals everywhere except
 * faces that meet at an angle greater than the crease angle.
 *
 * @param {BufferGeometry} geometry - The geometry to modify.
 * @param {number} [creaseAngle=Math.PI/3] - The crease angle in radians.
 * @return {BufferGeometry} - The updated geometry
 */
function toCreasedNormals( geometry, creaseAngle = Math.PI / 3 /* 60 degrees */ ) {

	const creaseDot = Math.cos( creaseAngle );
	const hashMultiplier = ( 1 + 1e-10 ) * 1e2;

	// reusable vectors
	const verts = [ new Vector3(), new Vector3(), new Vector3() ];
	const tempVec1 = new Vector3();
	const tempVec2 = new Vector3();
	const tempNorm = new Vector3();
	const tempNorm2 = new Vector3();

	// hashes a vector
	function hashVertex( v ) {

		const x = ~ ~ ( v.x * hashMultiplier );
		const y = ~ ~ ( v.y * hashMultiplier );
		const z = ~ ~ ( v.z * hashMultiplier );
		return `${x},${y},${z}`;

	}

	// BufferGeometry.toNonIndexed() warns if the geometry is non-indexed
	// and returns the original geometry
	const resultGeometry = geometry.index ? geometry.toNonIndexed() : geometry;
	const posAttr = resultGeometry.attributes.position;
	const vertexMap = {};

	// find all the normals shared by commonly located vertices
	for ( let i = 0, l = posAttr.count / 3; i < l; i ++ ) {

		const i3 = 3 * i;
		const a = verts[ 0 ].fromBufferAttribute( posAttr, i3 + 0 );
		const b = verts[ 1 ].fromBufferAttribute( posAttr, i3 + 1 );
		const c = verts[ 2 ].fromBufferAttribute( posAttr, i3 + 2 );

		tempVec1.subVectors( c, b );
		tempVec2.subVectors( a, b );

		// add the normal to the map for all vertices
		const normal = new Vector3().crossVectors( tempVec1, tempVec2 ).normalize();
		for ( let n = 0; n < 3; n ++ ) {

			const vert = verts[ n ];
			const hash = hashVertex( vert );
			if ( ! ( hash in vertexMap ) ) {

				vertexMap[ hash ] = [];

			}

			vertexMap[ hash ].push( normal );

		}

	}

	// average normals from all vertices that share a common location if they are within the
	// provided crease threshold
	const normalArray = new Float32Array( posAttr.count * 3 );
	const normAttr = new BufferAttribute( normalArray, 3, false );
	for ( let i = 0, l = posAttr.count / 3; i < l; i ++ ) {

		// get the face normal for this vertex
		const i3 = 3 * i;
		const a = verts[ 0 ].fromBufferAttribute( posAttr, i3 + 0 );
		const b = verts[ 1 ].fromBufferAttribute( posAttr, i3 + 1 );
		const c = verts[ 2 ].fromBufferAttribute( posAttr, i3 + 2 );

		tempVec1.subVectors( c, b );
		tempVec2.subVectors( a, b );

		tempNorm.crossVectors( tempVec1, tempVec2 ).normalize();

		// average all normals that meet the threshold and set the normal value
		for ( let n = 0; n < 3; n ++ ) {

			const vert = verts[ n ];
			const hash = hashVertex( vert );
			const otherNormals = vertexMap[ hash ];
			tempNorm2.set( 0, 0, 0 );

			for ( let k = 0, lk = otherNormals.length; k < lk; k ++ ) {

				const otherNorm = otherNormals[ k ];
				if ( tempNorm.dot( otherNorm ) > creaseDot ) {

					tempNorm2.add( otherNorm );

				}

			}

			tempNorm2.normalize();
			normAttr.setXYZ( i3 + n, tempNorm2.x, tempNorm2.y, tempNorm2.z );

		}

	}

	resultGeometry.setAttribute( 'normal', normAttr );
	return resultGeometry;

}


__mergeGeometries = mergeGeometries;
}
const mergeGeometries = __mergeGeometries;
let __RoomEnvironment;
{
const {
 	BackSide,
 	BoxGeometry,
 	InstancedMesh,
 	Mesh,
	MeshLambertMaterial,
 	MeshStandardMaterial,
 	PointLight,
 	Scene,
 	Object3D,
} = THREE;

/**
 * This class represents a scene with a basic room setup that can be used as
 * input for {@link PMREMGenerator#fromScene}. The resulting PMREM represents the room's
 * lighting and can be used for Image Based Lighting by assigning it to {@link Scene#environment}
 * or directly as an environment map to PBR materials.
 *
 * The implementation is based on the [EnvironmentScene](https://github.com/google/model-viewer/blob/master/packages/model-viewer/src/three-components/EnvironmentScene.ts)
 * component from the `model-viewer` project.
 *
 * ```js
 * const environment = new RoomEnvironment();
 * const pmremGenerator = new THREE.PMREMGenerator( renderer );
 *
 * const envMap = pmremGenerator.fromScene( environment ).texture;
 * scene.environment = envMap;
 * ```
 *
 * @augments Scene
 * @three_import import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
 */
class RoomEnvironment extends Scene {

	constructor() {

		super();

		const geometry = new BoxGeometry();
		geometry.deleteAttribute( 'uv' );

		const roomMaterial = new MeshStandardMaterial( { side: BackSide } );
		const boxMaterial = new MeshStandardMaterial();

		const mainLight = new PointLight( 0xffffff, 900, 28, 2 );
		mainLight.position.set( 0.418, 16.199, 0.300 );
		this.add( mainLight );

		const room = new Mesh( geometry, roomMaterial );
		room.position.set( - 0.757, 13.219, 0.717 );
		room.scale.set( 31.713, 28.305, 28.591 );
		this.add( room );

		const boxes = new InstancedMesh( geometry, boxMaterial, 6 );
		const transform = new Object3D();

		// box1
		transform.position.set( - 10.906, 2.009, 1.846 );
		transform.rotation.set( 0, - 0.195, 0 );
		transform.scale.set( 2.328, 7.905, 4.651 );
		transform.updateMatrix();
		boxes.setMatrixAt( 0, transform.matrix );

		// box2
		transform.position.set( - 5.607, - 0.754, - 0.758 );
		transform.rotation.set( 0, 0.994, 0 );
		transform.scale.set( 1.970, 1.534, 3.955 );
		transform.updateMatrix();
		boxes.setMatrixAt( 1, transform.matrix );

		// box3
		transform.position.set( 6.167, 0.857, 7.803 );
		transform.rotation.set( 0, 0.561, 0 );
		transform.scale.set( 3.927, 6.285, 3.687 );
		transform.updateMatrix();
		boxes.setMatrixAt( 2, transform.matrix );

		// box4
		transform.position.set( - 2.017, 0.018, 6.124 );
		transform.rotation.set( 0, 0.333, 0 );
		transform.scale.set( 2.002, 4.566, 2.064 );
		transform.updateMatrix();
		boxes.setMatrixAt( 3, transform.matrix );

		// box5
		transform.position.set( 2.291, - 0.756, - 2.621 );
		transform.rotation.set( 0, - 0.286, 0 );
		transform.scale.set( 1.546, 1.552, 1.496 );
		transform.updateMatrix();
		boxes.setMatrixAt( 4, transform.matrix );

		// box6
		transform.position.set( - 2.193, - 0.369, - 5.547 );
		transform.rotation.set( 0, 0.516, 0 );
		transform.scale.set( 3.875, 3.487, 2.986 );
		transform.updateMatrix();
		boxes.setMatrixAt( 5, transform.matrix );

		this.add( boxes );


		// -x right
		const light1 = new Mesh( geometry, createAreaLightMaterial( 50 ) );
		light1.position.set( - 16.116, 14.37, 8.208 );
		light1.scale.set( 0.1, 2.428, 2.739 );
		this.add( light1 );

		// -x left
		const light2 = new Mesh( geometry, createAreaLightMaterial( 50 ) );
		light2.position.set( - 16.109, 18.021, - 8.207 );
		light2.scale.set( 0.1, 2.425, 2.751 );
		this.add( light2 );

		// +x
		const light3 = new Mesh( geometry, createAreaLightMaterial( 17 ) );
		light3.position.set( 14.904, 12.198, - 1.832 );
		light3.scale.set( 0.15, 4.265, 6.331 );
		this.add( light3 );

		// +z
		const light4 = new Mesh( geometry, createAreaLightMaterial( 43 ) );
		light4.position.set( - 0.462, 8.89, 14.520 );
		light4.scale.set( 4.38, 5.441, 0.088 );
		this.add( light4 );

		// -z
		const light5 = new Mesh( geometry, createAreaLightMaterial( 20 ) );
		light5.position.set( 3.235, 11.486, - 12.541 );
		light5.scale.set( 2.5, 2.0, 0.1 );
		this.add( light5 );

		// +y
		const light6 = new Mesh( geometry, createAreaLightMaterial( 100 ) );
		light6.position.set( 0.0, 20.0, 0.0 );
		light6.scale.set( 1.0, 0.1, 1.0 );
		this.add( light6 );

	}

	/**
	 * Frees internal resources. This method should be called
	 * when the environment is no longer required.
	 */
	dispose() {

		const resources = new Set();

		this.traverse( ( object ) => {

			if ( object.isMesh ) {

				resources.add( object.geometry );
				resources.add( object.material );

			}

		} );

		for ( const resource of resources ) {

			resource.dispose();

		}

	}

}

function createAreaLightMaterial( intensity ) {

	// create an emissive-only material. see #31348
	const material = new MeshLambertMaterial( {
		color: 0x000000,
		emissive: 0xffffff,
		emissiveIntensity: intensity
	} );

	return material;

}


__RoomEnvironment = RoomEnvironment;
}
const RoomEnvironment = __RoomEnvironment;
let __RoundedBoxGeometry;
{
const {
	BoxGeometry,
	Vector3
} = THREE;

const _tempNormal = new Vector3();

function getUv( faceDirVector, normal, uvAxis, projectionAxis, radius, sideLength ) {

	const totArcLength = 2 * Math.PI * radius / 4;

	// length of the planes between the arcs on each axis
	const centerLength = Math.max( sideLength - 2 * radius, 0 );
	const halfArc = Math.PI / 4;

	// Get the vector projected onto the Y plane
	_tempNormal.copy( normal );
	_tempNormal[ projectionAxis ] = 0;
	_tempNormal.normalize();

	// total amount of UV space alloted to a single arc
	const arcUvRatio = 0.5 * totArcLength / ( totArcLength + centerLength );

	// the distance along one arc the point is at
	const arcAngleRatio = 1.0 - ( _tempNormal.angleTo( faceDirVector ) / halfArc );

	if ( Math.sign( _tempNormal[ uvAxis ] ) === 1 ) {

		return arcAngleRatio * arcUvRatio;

	} else {

		// total amount of UV space alloted to the plane between the arcs
		const lenUv = centerLength / ( totArcLength + centerLength );
		return lenUv + arcUvRatio + arcUvRatio * ( 1.0 - arcAngleRatio );

	}

}

/**
 * A special type of box geometry with rounded corners and edges.
 *
 * ```js
 * const geometry = new THREE.RoundedBoxGeometry();
 * const material = new THREE.MeshStandardMaterial( { color: 0x00ff00 } );
 * const cube = new THREE.Mesh( geometry, material );
 * scene.add( cube );
 * ```
 *
 * @augments BoxGeometry
 * @three_import import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';
 */
class RoundedBoxGeometry extends BoxGeometry {

	/**
	 * Constructs a new rounded box geometry.
	 *
	 * @param {number} [width=1] - The width. That is, the length of the edges parallel to the X axis.
	 * @param {number} [height=1] - The height. That is, the length of the edges parallel to the Y axis.
	 * @param {number} [depth=1] - The depth. That is, the length of the edges parallel to the Z axis.
	 * @param {number} [segments=2] - Number of segments that form the rounded corners.
	 * @param {number} [radius=0.1] - The radius of the rounded corners.
	 */
	constructor( width = 1, height = 1, depth = 1, segments = 2, radius = 0.1 ) {

		// calculate total segments needed &
		// ensure it's odd so that we have a plane connecting the rounded corners
		const totalSegments = segments * 2 + 1;

		// ensure radius isn't bigger than shortest side
		radius = Math.min( width / 2, height / 2, depth / 2, radius );

		// start with a unit box geometry, its vertices will be modified to form the rounded box
		super( 1, 1, 1, totalSegments, totalSegments, totalSegments );

		this.type = 'RoundedBoxGeometry';

		/**
		 * Holds the constructor parameters that have been
		 * used to generate the geometry. Any modification
		 * after instantiation does not change the geometry.
		 *
		 * @type {Object}
		 */
		this.parameters = {
			width: width,
			height: height,
			depth: depth,
			segments: segments,
			radius: radius,
		};

		// if totalSegments is 1, no rounding is needed - return regular box
		if ( totalSegments === 1 ) return;

		const geometry2 = this.toNonIndexed();

		this.index = null;
		this.attributes.position = geometry2.attributes.position;
		this.attributes.normal = geometry2.attributes.normal;
		this.attributes.uv = geometry2.attributes.uv;

		//

		const position = new Vector3();
		const normal = new Vector3();

		const box = new Vector3( width, height, depth ).divideScalar( 2 ).subScalar( radius );

		const positions = this.attributes.position.array;
		const normals = this.attributes.normal.array;
		const uvs = this.attributes.uv.array;

		const faceTris = positions.length / 6;
		const faceDirVector = new Vector3();
		const halfSegmentSize = 0.5 / totalSegments;

		for ( let i = 0, j = 0; i < positions.length; i += 3, j += 2 ) {

			position.fromArray( positions, i );
			normal.copy( position );
			normal.x -= Math.sign( normal.x ) * halfSegmentSize;
			normal.y -= Math.sign( normal.y ) * halfSegmentSize;
			normal.z -= Math.sign( normal.z ) * halfSegmentSize;
			normal.normalize();

			positions[ i + 0 ] = box.x * Math.sign( position.x ) + normal.x * radius;
			positions[ i + 1 ] = box.y * Math.sign( position.y ) + normal.y * radius;
			positions[ i + 2 ] = box.z * Math.sign( position.z ) + normal.z * radius;

			normals[ i + 0 ] = normal.x;
			normals[ i + 1 ] = normal.y;
			normals[ i + 2 ] = normal.z;

			const side = Math.floor( i / faceTris );

			switch ( side ) {

				case 0: // right

					// generate UVs along Z then Y
					faceDirVector.set( 1, 0, 0 );
					uvs[ j + 0 ] = getUv( faceDirVector, normal, 'z', 'y', radius, depth );
					uvs[ j + 1 ] = 1.0 - getUv( faceDirVector, normal, 'y', 'z', radius, height );
					break;

				case 1: // left

					// generate UVs along Z then Y
					faceDirVector.set( - 1, 0, 0 );
					uvs[ j + 0 ] = 1.0 - getUv( faceDirVector, normal, 'z', 'y', radius, depth );
					uvs[ j + 1 ] = 1.0 - getUv( faceDirVector, normal, 'y', 'z', radius, height );
					break;

				case 2: // top

					// generate UVs along X then Z
					faceDirVector.set( 0, 1, 0 );
					uvs[ j + 0 ] = 1.0 - getUv( faceDirVector, normal, 'x', 'z', radius, width );
					uvs[ j + 1 ] = getUv( faceDirVector, normal, 'z', 'x', radius, depth );
					break;

				case 3: // bottom

					// generate UVs along X then Z
					faceDirVector.set( 0, - 1, 0 );
					uvs[ j + 0 ] = 1.0 - getUv( faceDirVector, normal, 'x', 'z', radius, width );
					uvs[ j + 1 ] = 1.0 - getUv( faceDirVector, normal, 'z', 'x', radius, depth );
					break;

				case 4: // front

					// generate UVs along X then Y
					faceDirVector.set( 0, 0, 1 );
					uvs[ j + 0 ] = 1.0 - getUv( faceDirVector, normal, 'x', 'y', radius, width );
					uvs[ j + 1 ] = 1.0 - getUv( faceDirVector, normal, 'y', 'x', radius, height );
					break;

				case 5: // back

					// generate UVs along X then Y
					faceDirVector.set( 0, 0, - 1 );
					uvs[ j + 0 ] = getUv( faceDirVector, normal, 'x', 'y', radius, width );
					uvs[ j + 1 ] = 1.0 - getUv( faceDirVector, normal, 'y', 'x', radius, height );
					break;

			}

		}

	}

	/**
	 * Factory method for creating an instance of this class from the given
	 * JSON object.
	 *
	 * @param {Object} data - A JSON object representing the serialized geometry.
	 * @returns {RoundedBoxGeometry} A new instance.
	 */
	static fromJSON( data ) {

		return new RoundedBoxGeometry(
			data.width,
			data.height,
			data.depth,
			data.segments,
			data.radius
		);

	}


}


__RoundedBoxGeometry = RoundedBoxGeometry;
}
const RoundedBoxGeometry = __RoundedBoxGeometry;
/* o'yin yadrosi */
/** Deterministic 60 Hz simulation shared by WebGL client and server replay. */
const TICK_RATE = 60;
const MAX_TICKS = 108000;
const SIMULATION_VERSION = 'uz-manual-v4';
const MAX_SPEED = 280;
const MIN_SPEED = 0;
const THROTTLE_ACCELERATION = 24;
const COAST_DECELERATION = 6;
const BRAKE_DECELERATION = 42;
const MAX_RUN_INPUTS = 15000;
const TRAFFIC_SPEED = 60;
const MIN_TRAFFIC_GAP = 52;
const MAX_TRAFFIC_GAP = 68;
const LANES = [-5.25, -1.75, 1.75, 5.25]         ;
// Every selectable player body uses this same hitbox: appearance grants no advantage.
const PLAYER_COLLISION_DIMENSIONS = { width: 1.94, length: 4.45 }         ;
const TRAFFIC_DIMENSIONS = [
  { width: 1.78, length: 4.5 }, // Gentra
  { width: 1.86, length: 4.85 }, // Malibu
  { width: 2.2, length: 7.9 }, // Isuzu
  { width: 1.45, length: 3.5 }, // Damas
  { width: 1.5, length: 3.4 }, // Matiz
  { width: 1.6, length: 3.65 }, // Spark
]         ;
const SPAWN_Z = -260;
                                   
                                                                 
                                                                         
                                                                                                                                         
                            
                                                                                           
                                                                                        
 
                                                    
                                                                                           
                                                                                           
 
function random(s                 )         {
  s.rng = (Math.imul(s.rng, 1664525) + 1013904223) >>> 0;
  return s.rng / 4294967296;
}
function spawnCar(s                 , z        ) {
  const lane = Math.floor(random(s) * LANES.length);
  s.traffic.push({ id: s.nextId++, lane, x: LANES[lane], z, speed: TRAFFIC_SPEED,
    kind: Math.floor(random(s) * TRAFFIC_DIMENSIONS.length), color: Math.floor(random(s) * 6), passed: false });
}
function nextSpacing(s                 )         {
  return MIN_TRAFFIC_GAP + random(s) * (MAX_TRAFFIC_GAP - MIN_TRAFFIC_GAP);
}
function collisionHalfSizes(kind        )                                    {
  const traffic = TRAFFIC_DIMENSIONS[kind] ?? TRAFFIC_DIMENSIONS[0];
  return { width: (PLAYER_COLLISION_DIMENSIONS.width + traffic.width) / 2 - 0.12,
    length: (PLAYER_COLLISION_DIMENSIONS.length + traffic.length) / 2 - 0.15 };
}
function createSimulation(seed        )                  {
  const s                  = { seed: seed >>> 0, rng: seed >>> 0, ticks: 0, lane: 1, x: LANES[1],
    distance: 0, speed: 100, speedLimit: MAX_SPEED, elapsed: 0, nearMisses: 0, combo: 0, level: 1,
    throttle: false, brake: false, acceleration: 0, traffic: [],
    nextId: 0, spawnIn: 0, crashed: false, finished: false, comboUntil: 0 };
  // A single car per longitudinal slot: never a wall of cars across the road.
  spawnCar(s, -68); spawnCar(s, -126); spawnCar(s, -184);
  s.spawnIn = nextSpacing(s);
  return s;
}
function stepSimulation(s                 , direction            = 0, pedals             )                  {
  if (s.crashed || s.finished) return s;
  if (pedals?.throttle !== undefined) s.throttle = pedals.throttle;
  if (pedals?.brake !== undefined) s.brake = pedals.brake;
  if (direction) s.lane = Math.max(0, Math.min(3, s.lane + direction));
  // The full speed range is available immediately; only pedal input changes speed.
  s.speedLimit = MAX_SPEED;
  s.level = 1;
  const previousSpeed = s.speed;
  const acceleration = s.brake ? -BRAKE_DECELERATION : s.throttle ? THROTTLE_ACCELERATION : -COAST_DECELERATION;
  s.speed = Math.max(MIN_SPEED, Math.min(s.speedLimit, s.speed + acceleration / TICK_RATE));
  s.acceleration = (s.speed - previousSpeed) * TICK_RATE;
  // Turning the wheel selects a lane at rest; the body moves only as the car rolls.
  const lateralSpeed = 13 * Math.min(1, s.speed / 40);
  const dx = LANES[s.lane] - s.x;
  s.x += Math.sign(dx) * Math.min(Math.abs(dx), lateralSpeed / TICK_RATE);
  // Trapezoidal integration gives consistent stopping distance at60Hz.
  const averageSpeed = (previousSpeed + s.speed) / 2;
  s.distance += averageSpeed / 3.6 / TICK_RATE;
  s.ticks++;
  s.elapsed = s.ticks / TICK_RATE;
  if (s.ticks > s.comboUntil) s.combo = 0;
  for (const car of s.traffic) {
    car.z += (averageSpeed - car.speed) / 3.6 / TICK_RATE;
    const half = collisionHalfSizes(car.kind);
    // Drivers already behind the player yield when it slows. Front traffic never
    // changes lane, and all speeds stay equal, so the52m longitudinal gaps persist.
    const occupiedLane = Math.max(0, Math.min(LANES.length - 1, Math.round((s.x - LANES[0]) / 3.5)));
    if (car.z > 0 && (s.brake || s.speed < TRAFFIC_SPEED + 8) && car.lane === occupiedLane && Math.abs(car.x - s.x) < half.width + 0.35) {
      car.lane = occupiedLane === LANES.length - 1 ? occupiedLane - 1 : occupiedLane + 1;
    }
    const trafficDx = LANES[car.lane] - car.x;
    car.x += Math.sign(trafficDx) * Math.min(Math.abs(trafficDx), 8 / TICK_RATE);
    if (Math.abs(car.z) < half.length && Math.abs(car.x - s.x) < half.width) {
      s.crashed = true;
    }
    if (!car.passed && car.z > half.length + 0.2) {
      car.passed = true;
      const gap = Math.abs(car.x - s.x);
      if (gap < half.width + 1.2 && gap >= half.width && !s.crashed) {
        s.nearMisses++;
        s.combo = Math.min(99, s.combo + 1);
        s.comboUntil = s.ticks + 240;
      }
    }
  }
  s.traffic = s.traffic.filter(c => c.z < 25 && c.z > -420);
  const furthestAhead = s.traffic.reduce((min, car) => Math.min(min, car.z), Infinity);
  const nextZ = furthestAhead === Infinity ? SPAWN_Z : furthestAhead - s.spawnIn;
  if (Number.isFinite(s.spawnIn) && nextZ >= SPAWN_Z) {
    // Spacing is measured in metres, not ticks, so faster play never bunches cars.
    // Equal traffic speeds preserve every existing gap exactly. With >=52 m between
    // cars, only one lane can be obstructed locally and an adjacent exit is reachable.
    spawnCar(s, nextZ);
    s.spawnIn = nextSpacing(s);
  }
  s.finished = s.ticks >= MAX_TICKS;
  return s;
}
function validateInputs(inputs            , ticks        )       {
  if (!Number.isInteger(ticks) || ticks < 1 || ticks > MAX_TICKS) throw new Error('Invalid run duration');
  if (!Array.isArray(inputs) || inputs.length > ticks || inputs.length > MAX_RUN_INPUTS) throw new Error('Invalid controls');
  let prev = -1;
  for (const input of inputs) {
    if (!input || typeof input !== 'object' || Array.isArray(input) || !Number.isSafeInteger(input.tick) || input.tick <= prev || input.tick >= ticks ||
      Object.keys(input).some(key => !['tick', 'direction', 'throttle', 'brake'].includes(key)) ||
      (input.direction !== undefined && input.direction !== -1 && input.direction !== 1) ||
      (input.throttle !== undefined && typeof input.throttle !== 'boolean') ||
      (input.brake !== undefined && typeof input.brake !== 'boolean') ||
      (input.direction === undefined && input.throttle === undefined && input.brake === undefined)) throw new Error('Invalid control sequence');
    prev = input.tick;
  }
}
function replayRun(seed        , inputs            , ticks        )                  {
  validateInputs(inputs, ticks);
  const s = createSimulation(seed);
  let index = 0;
  for (let tick = 0; tick < ticks; tick++) {
    if (s.crashed || s.finished) throw new Error('Run continued after collision');
    const input = inputs[index]?.tick === tick ? inputs[index++] : undefined;
    stepSimulation(s, input?.direction ?? 0, input);
  }
  return s;
}




/** Local procedural models: no downloads, remote meshes, or licensed model assets. */
const VEHICLE_MODELS = [
  { id: 0, name: 'Gentra', description: 'Klassik sedan', width: 1.78, length: 4.5 },
  { id: 1, name: 'Malibu', description: 'Keng sport sedan', width: 1.86, length: 4.85 },
  { id: 2, name: 'Isuzu', description: 'Yuk avtomobili', width: 2.2, length: 7.9 },
  { id: 3, name: 'Damas', description: 'Shahar mikroveni', width: 1.45, length: 3.5 },
  { id: 4, name: 'Matiz', description: 'Ixcham xetchbek', width: 1.5, length: 3.4 },
  { id: 5, name: 'Spark', description: 'Zamonaviy xetchbek', width: 1.6, length: 3.65 },
]         ;

                                                                                                                         

function buildVehicle({ model, color, isPlayer = false, paintMaterial }                )              {
  const spec = VEHICLE_MODELS[model] ?? VEHICLE_MODELS[1];
  const car = new THREE.Group();
  car.name = spec.name;
  car.userData.model = spec.id;
  const paint = paintMaterial ?? new THREE.MeshPhysicalMaterial({ color, metalness: 0.58, roughness: 0.22,
    clearcoat: 1, clearcoatRoughness: 0.065, envMapIntensity: 1.35 });
  const standard = (c        , roughness = 0.5, metalness = 0.3) => new THREE.MeshStandardMaterial({ color: c, roughness, metalness });
  const black = standard('#111923', 0.42);
  const chrome = standard('#b8c4cc', 0.18, 0.85);
  const glass = new THREE.MeshPhysicalMaterial({ color: '#142f3d', metalness: 0.13, roughness: 0.075,
    clearcoat: 1, clearcoatRoughness: 0.04, reflectivity: 0.95, envMapIntensity: 1.7 });
  const red = new THREE.MeshStandardMaterial({ color: '#831b21', emissive: '#ff241b', emissiveIntensity: 0.65,
    roughness: 0.2, metalness: 0.1, toneMapped: false });
  const amber = new THREE.MeshBasicMaterial({ color: '#ffaf38', toneMapped: false });
  const white = new THREE.MeshStandardMaterial({ color: '#bed7e8', emissive: '#c7e9ff', emissiveIntensity: 1.9,
    roughness: 0.12, toneMapped: false });
  const tire = standard('#101318', 0.9, 0.05);
  const treadCanvas = document.createElement('canvas'); treadCanvas.width = 128; treadCanvas.height = 64;
  const treadCtx = treadCanvas.getContext('2d') ;
  treadCtx.fillStyle = '#a3a7ad'; treadCtx.fillRect(0, 0, 128, 64);
  treadCtx.strokeStyle = '#4c5057'; treadCtx.lineWidth = 2;
  for (let i = -16; i < 160; i += 12) {
    treadCtx.beginPath(); treadCtx.moveTo(i, 0); treadCtx.lineTo(i + 9, 23);
    treadCtx.lineTo(i + 4, 41); treadCtx.lineTo(i + 13, 64); treadCtx.stroke();
  }
  for (const y of [19, 44]) { treadCtx.beginPath(); treadCtx.moveTo(0, y); treadCtx.lineTo(128, y); treadCtx.stroke(); }
  const treadTexture = new THREE.CanvasTexture(treadCanvas); treadTexture.wrapS = treadTexture.wrapT = THREE.RepeatWrapping;
  treadTexture.repeat.set(2, 1); tire.bumpMap = treadTexture; tire.bumpScale = 0.01; tire.roughnessMap = treadTexture;
  const cargo = standard('#d4d8dc', 0.58, 0.25);
  car.userData.brakeMaterial = red;
  car.userData.wheels = []                 ;
  const box = (w        , h        , d        , mat                , x = 0, y = 0, z = 0, rx = 0, ry = 0, rz = 0) => {
    const geometry = (mat === paint || mat === cargo) && Math.min(w, h, d) > 0.095
      ? new RoundedBoxGeometry(w, h, d, 2, Math.min(0.055, w / 8, h / 8, d / 8))
      : new THREE.BoxGeometry(w, h, d);
    const m = new THREE.Mesh(geometry, mat);
    m.position.set(x, y, z); m.rotation.set(rx, ry, rz); car.add(m); return m;
  };
  const profile = (width        , points                    , material                ) => {
    // Shape X is -world-Z, extrusion becomes world-X after rotation.
    const shape = new THREE.Shape();
    shape.moveTo(-points[0][0], points[0][1]);
    for (let i = 1; i < points.length; i++) shape.lineTo(-points[i][0], points[i][1]);
    shape.closePath();
    const bevel = material === glass ? 0.012 : 0.035;
    const geo = new THREE.ExtrudeGeometry(shape, { depth: width - bevel * 2, bevelEnabled: true,
      bevelThickness: bevel, bevelSize: bevel, bevelSegments: 2, steps: 1 });
    geo.rotateY(Math.PI / 2); geo.translate(-(width - bevel * 2) / 2, 0, 0);
    const m = new THREE.Mesh(geo, material); m.castShadow = true; car.add(m); return m;
  };
  const wheel = (x        , z        , r        ) => {
    const group = new THREE.Group();
    group.name = 'wheel';
    group.userData.isWheel = true; group.userData.radius = r; group.userData.front = z < 0;
    const depth = spec.id === 2 ? 0.31 : 0.23;
    // Curved sidewalls and a tread surface instead of a plain cylinder.
    const tireProfile = [new THREE.Vector2(r * 0.67, -depth / 2), new THREE.Vector2(r * 0.9, -depth / 2),
      new THREE.Vector2(r * 0.985, -depth * 0.32), new THREE.Vector2(r, -depth * 0.17),
      new THREE.Vector2(r, depth * 0.17), new THREE.Vector2(r * 0.985, depth * 0.32),
      new THREE.Vector2(r * 0.9, depth / 2), new THREE.Vector2(r * 0.67, depth / 2)];
    const rubber = new THREE.Mesh(new THREE.LatheGeometry(tireProfile, 24), tire);
    rubber.rotation.z = Math.PI / 2; group.add(rubber);
    const disk = new THREE.Mesh(new THREE.CylinderGeometry(r * 0.57, r * 0.57, depth * 0.66, 20), standard('#30373d', 0.45, 0.75));
    disk.rotation.z = Math.PI / 2; group.add(disk);
    const rimGeometry                         = [];
    const ring = new THREE.TorusGeometry(r * 0.66, r * 0.055, 6, 24);
    ring.rotateY(Math.PI / 2); ring.translate((x < 0 ? -1 : 1) * depth * 0.49, 0, 0); rimGeometry.push(ring);
    const spokeCount = spec.id === 2 || spec.id === 3 ? 6 : 5;
    for (let i = 0; i < spokeCount; i++) {
      const angle = i / spokeCount * Math.PI * 2;
      const spoke = new THREE.BoxGeometry(depth * 0.94, r * 0.075, r * 0.53).toNonIndexed();
      spoke.translate(0, 0, r * 0.34); spoke.rotateX(angle); rimGeometry.push(spoke);
    }
    const hub = new THREE.CylinderGeometry(r * 0.2, r * 0.2, depth * 0.98, 12);
    hub.rotateZ(Math.PI / 2); rimGeometry.push(hub.toNonIndexed());
    const mergedRim = mergeGeometries(rimGeometry.map(g => g.index ? g.toNonIndexed() : g));
    if (mergedRim) group.add(new THREE.Mesh(mergedRim, chrome));
    for (const geometry of rimGeometry) geometry.dispose();
    group.position.set(x, r, z); car.add(group);
    car.userData.wheels.push(group);
    // The arch trim stays with the body while the wheel itself rotates.
    const archGeometry = new THREE.TorusGeometry(r * 1.08, r * 0.075, 5, 16, Math.PI);
    archGeometry.rotateY(Math.PI / 2);
    const arch = new THREE.Mesh(archGeometry, black);
    arch.position.set(x + Math.sign(x) * 0.115, r, z); car.add(arch);
  };
  const wheels = (halfWidth        , front        , rear        , radius        ) => {
    for (const x of [-halfWidth, halfWidth]) for (const z of [front, rear]) wheel(x, z, radius);
  };
  const plate = (label        , z        , y        , w = 0.75, front = false, h = 0.17) => {
    const canvas = document.createElement('canvas'); canvas.width = 256; canvas.height = 64;
    const ctx = canvas.getContext('2d') ;
    ctx.fillStyle = '#e8ede8'; ctx.fillRect(0, 0, 256, 64);
    ctx.fillStyle = '#183947'; ctx.fillRect(0, 0, 7, 64);
    ctx.font = `bold ${label.length > 6 ? 32 : 39}px sans-serif`;
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(label.toUpperCase(), 132, 34);
    const texture = new THREE.CanvasTexture(canvas); texture.colorSpace = THREE.SRGBColorSpace;
    const m = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ map: texture }));
    m.position.set(0, y, z); if (front) m.rotation.y = Math.PI; car.add(m);
  };
  const sideMirrors = (halfWidth        , y        , z        ) => {
    for (const side of [-1, 1]) {
      box(0.16, 0.06, 0.07, black, side * (halfWidth + 0.04), y, z);
      box(0.19, 0.12, 0.22, paint, side * (halfWidth + 0.14), y + 0.03, z);
    }
  };

  if (spec.id === 2) {
    // ISUZU cab-over: vertical cab ahead of a long, high cargo box.
    profile(2.08, [[-3.95, 0.48], [-3.95, 2.35], [-3.6, 2.64], [-2.03, 2.64], [-2.03, 0.48]], paint);
    box(2.2, 2.6, 5.45, cargo, 0, 1.92, 1.225).castShadow = true;
    box(1.9, 0.84, 0.025, glass, 0, 1.94, -3.969);
    box(1.98, 0.06, 0.045, black, 0, 1.49, -3.985);
    box(1.15, 0.28, 0.05, black, 0, 0.89, -3.98);
    box(2.11, 0.21, 0.12, chrome, 0, 0.5, -3.98);
    plate('ISUZU', -4.055, 1.18, 1.0, true, 0.25);
    for (const side of [-1, 1]) {
      box(0.026, 0.77, 1.19, glass, side * 1.052, 1.98, -2.82);
      box(0.035, 0.04, 1.22, black, side * 1.065, 1.55, -2.81);
      box(0.055, 0.07, 0.23, chrome, side * 1.07, 1.43, -2.35);
      box(0.17, 0.29, 0.18, black, side * 1.22, 1.9, -3.67);
      box(0.07, 0.07, 0.39, black, side * 1.12, 1.76, -3.68);
      box(0.32, 0.24, 0.04, white, side * 0.76, 0.88, -4.005);
      box(0.32, 0.07, 0.04, amber, side * 0.76, 1.065, -4.005);
      // Rear cargo doors and reflective strips.
      box(0.035, 2.35, 0.055, chrome, side * 0.52, 1.93, 3.98);
      box(0.86, 0.1, 0.055, amber, side * 0.57, 0.65, 4);
      box(0.32, 0.18, 0.06, red, side * 0.79, 0.44, 4.01);
      for (const z of [-3.14, 2.18, 3.06]) wheel(side * 1.025, z, 0.47);
    }
    box(0.025, 2.58, 0.04, black, 0, 1.92, 3.975);
    box(2.3, 0.17, 0.14, black, 0, 0.35, 4);
    plate('ISUZU', 4.085, 1.35, 1.2, false, 0.28);
  } else if (spec.id === 3) {
    // DAMAS: narrow, tall, almost flat-front microvan and tiny wheels.
    profile(1.45, [[-1.75, 0.32], [-1.75, 1.44], [-1.49, 1.98], [1.54, 1.98], [1.75, 1.83], [1.75, 0.32]], paint);
    box(1.22, 0.6, 0.03, glass, 0, 1.67, -1.672, 0.45);
    box(1.24, 0.64, 0.03, glass, 0, 1.49, 1.772);
    box(1.38, 0.18, 0.1, black, 0, 0.49, -1.78);
    box(0.66, 0.12, 0.04, black, 0, 0.81, -1.777);
    box(1.4, 0.16, 0.1, black, 0, 0.43, 1.8);
    for (const side of [-1, 1]) {
      for (const [z, depth] of [[-0.99, 0.88], [0.05, 0.9], [1.05, 0.82]]) box(0.024, 0.6, depth, glass, side * 0.737, 1.52, z);
      // Distinctive warm side stripe, sliding-door seam, and handle.
      box(0.025, 0.075, 3.18, amber, side * 0.744, 0.97, 0.05);
      box(0.03, 0.03, 2.1, black, side * 0.747, 1.13, 0.58);
      box(0.03, 0.83, 0.018, black, side * 0.748, 0.98, 0.52);
      box(0.04, 0.06, 0.22, black, side * 0.756, 1.19, -0.05);
      box(0.37, 0.22, 0.045, white, side * 0.45, 0.8, -1.8);
      box(0.1, 0.23, 0.045, amber, side * 0.66, 0.8, -1.8);
      box(0.15, 0.34, 0.045, red, side * 0.61, 0.76, 1.8);
    }
    sideMirrors(0.725, 1.39, -1.3);
    wheels(0.714, -1.03, 1.09, 0.26);
    plate('DAMAS', 1.84, 1.01, 0.72); plate('DAMAS', -1.84, 0.59, 0.6, true);
  } else if (spec.id === 4) {
    // MATIZ: egg-shaped cabin, very short hood, circular headlamps.
    profile(1.5, [[-1.7, 0.31], [-1.7, 0.61], [-1.53, 0.88], [-0.92, 1.01],
      [1.43, 1.01], [1.7, 0.87], [1.7, 0.31]], paint);
    profile(1.36, [[-0.95, 1.02], [-0.46, 1.41], [0.08, 1.48], [0.74, 1.43], [1.43, 1.18], [1.43, 1.02]], glass);
    box(1.33, 0.08, 0.88, paint, 0, 1.515, 0.23);
    for (const side of [-1, 1]) {
      box(0.075, 0.45, 0.075, paint, side * 0.69, 1.25, 0.2);
      const lamp = new THREE.Mesh(new THREE.CylinderGeometry(0.19, 0.19, 0.045, 16), white);
      lamp.rotation.x = Math.PI / 2; lamp.position.set(side * 0.47, 0.81, -1.625); car.add(lamp);
      box(0.12, 0.075, 0.04, amber, side * 0.63, 0.58, -1.71);
      box(0.15, 0.4, 0.05, red, side * 0.64, 0.92, 1.64, 0, 0, side * -0.12);
      box(0.16, 0.1, 0.06, amber, side * 0.64, 1.07, 1.65);
    }
    box(1.19, 0.13, 0.1, black, 0, 0.45, -1.71);
    box(1.35, 0.16, 0.1, black, 0, 0.45, 1.72);
    sideMirrors(0.75, 1.05, -0.67); wheels(0.735, -1.04, 1.04, 0.285);
    plate('MATIZ', 1.783, 0.73, 0.64); plate('MATIZ', -1.77, 0.46, 0.54, true);
  } else if (spec.id === 5) {
    // SPARK: taller angular hatch, swept headlamps and upright rear clusters.
    profile(1.6, [[-1.825, 0.31], [-1.825, 0.61], [-1.52, 0.91], [-0.86, 1.05],
      [1.53, 1.05], [1.825, 0.85], [1.825, 0.31]], paint);
    profile(1.43, [[-0.84, 1.04], [-0.25, 1.52], [0.85, 1.52], [1.48, 1.3], [1.5, 1.05]], glass);
    box(1.38, 0.09, 1.05, paint, 0, 1.595, 0.37);
    box(1.31, 0.06, 0.2, paint, 0, 1.56, 1.16);
    box(0.83, 0.23, 0.05, black, 0, 0.59, -1.84);
    box(0.91, 0.05, 0.065, chrome, 0, 0.76, -1.85);
    box(0.56, 0.08, 0.05, black, 0, 0.89, -1.68);
    for (const side of [-1, 1]) {
      box(0.09, 0.49, 0.09, paint, side * 0.735, 1.28, 0.48);
      box(0.36, 0.13, 0.06, white, side * 0.57, 0.91, -1.63, 0, side * 0.12, side * -0.2);
      box(0.18, 0.58, 0.06, red, side * 0.7, 1.02, 1.75, 0, 0, side * -0.1);
      box(0.1, 0.16, 0.065, white, side * 0.7, 1.05, 1.79);
      box(0.035, 0.05, 0.17, black, side * 0.82, 1.4, 0.99);
    }
    box(1.48, 0.18, 0.1, black, 0, 0.43, 1.85);
    sideMirrors(0.8, 1.08, -0.66); wheels(0.78, -1.13, 1.1, 0.31);
    plate('SPARK', 1.908, 0.78, 0.66); plate('SPARK', -1.895, 0.58, 0.57, true);
  } else {
    const malibu = spec.id === 1;
    const half = spec.length / 2, width = spec.width;
    const top = malibu ? 1.37 : 1.48;
    profile(width, [[-half, 0.36], [-half, 0.65], [-half + 0.28, 0.88], [-0.88, 0.95],
      [1.32, 0.96], [half - 0.12, 0.84], [half, 0.65], [half, 0.36]], paint);
    profile(width - 0.22, [[-0.91, 0.96], [malibu ? -0.28 : -0.42, top - 0.06],
      [malibu ? 0.72 : 0.66, top], [1.4, 0.99]], glass);
    box(width - 0.31, 0.1, malibu ? 1.1 : 1.23, paint, 0, top + 0.015, 0.25);
    const front = -half - 0.025, back = half + 0.025;
    // Malibu has a wide low grille and thin LED signatures; Gentra has square clusters.
    box(width * 0.58, malibu ? 0.25 : 0.18, 0.045, black, 0, malibu ? 0.56 : 0.7, front);
    box(width * 0.61, 0.04, 0.065, chrome, 0, malibu ? 0.73 : 0.78, front - 0.01);
    box(width * 0.8, 0.17, 0.1, black, 0, 0.42, back);
    for (const side of [-1, 1]) {
      const x = side * (width / 2 - 0.08);
      box(0.075, top - 0.93, 0.085, paint, x, (top + 0.93) / 2, 0.24);
      box(0.06, 0.055, 1.83, chrome, x, 0.97, 0.24);
      box(malibu ? 0.56 : 0.46, malibu ? 0.08 : 0.17, 0.04, white, side * width * 0.34, 0.8, front - 0.01);
      box(malibu ? 0.55 : 0.43, malibu ? 0.12 : 0.2, 0.045, red, side * width * 0.34, 0.8, back + 0.01);
      if (malibu) {
        box(0.065, 0.17, 0.05, red, side * width * 0.46, 0.77, back + 0.01);
        box(0.43, 0.04, 0.055, black, side * width * 0.34, 0.81, back + 0.04);
      } else box(0.14, 0.055, 0.05, amber, side * width * 0.43, 0.81, front - 0.02);
      for (const z of [-0.13, 0.8]) box(0.04, 0.045, 0.21, chrome, side * (width / 2 + 0.01), 0.98, z);
    }
    sideMirrors(width / 2, 1, -0.75);
    wheels(width / 2 - 0.02, malibu ? -1.48 : -1.35, malibu ? 1.44 : 1.35, malibu ? 0.36 : 0.33);
    plate(spec.name, back + 0.085, 0.6, 0.7); plate(spec.name, front - 0.08, 0.5, 0.6, true);
    if (isPlayer && malibu) {
      // Small factory-style boot lip, not an oversized generic racing wing.
      box(1.55, 0.055, 0.12, paint, 0, 0.93, 2.13);
    }
  }
  // Every player model uses one identical visible footprint and physics box.
  // Traffic retains the true relative dimensions to make Damas/Isuzu legible.
  if (isPlayer) {
    car.scale.set(1.94 / spec.width, spec.id === 2 ? 0.75 : Math.min(1.17, 4.45 / spec.length), 4.45 / spec.length);
  }
  return car;
}






const PALETTE = ['#c6cbd3', '#2585c2', '#df6357', '#f0bb53', '#717f8d', '#e2e5e9'];
const clamp = THREE.MathUtils.clamp;

/** Genuine 3D renderer and deterministic race loop. No remote models required. */
class RaceScene {
          scene = new THREE.Scene();
          camera = new THREE.PerspectiveCamera(54, 1, 0.1, 650);
          renderer                      ;
          root             ;
          callbacks               ;
          observer                 ;
          player              ;
          playerModel = 1;
          playerPaint = new THREE.MeshPhysicalMaterial({ color: '#dfff00', metalness: 0.58, roughness: 0.22,
    clearcoat: 1, clearcoatRoughness: 0.065, envMapIntensity: 1.35 });
          environmentTarget                          ;
          trafficModels = new Map                     ();
          stripeMeshes                        = [];
          roadside                = [];
          state = createSimulation(476921);
          inputs             = [];
          queue             = [];
          touchPedals = { throttle: false, brake: false };
          heldKeys = new Set        ();
          mode                                               = 'attract';
          last = 0;
          accumulator = 0;
          frame = 0;
          disposed = false;
          visualDistance = 0;
          lastUi = 0;
          attractTime = 0;
          hitAt = 0;
          sound               ;
          engine                 ;
          engineGain           ;
          muted = true;
          keyHandler                            ;
          keyUpHandler                            ;
          blurHandler = () => this.clearControls();

  constructor(element             , callbacks                = {}) {
    this.root = element;
    this.callbacks = callbacks;
    this.keyHandler = e => {
      if (this.mode !== 'playing') return;
      const key = e.key.toLowerCase();
      if (['arrowup', 'w', 'arrowdown', 's', ' '].includes(key)) {
        e.preventDefault(); this.heldKeys.add(key); return;
      }
      if (e.repeat) return;
      if (['ArrowLeft', 'a', 'A', 'ArrowRight', 'd', 'D'].includes(e.key)) {
        e.preventDefault();
        this.steer(['ArrowLeft', 'a', 'A'].includes(e.key) ? -1 : 1);
      }
    };
    this.keyUpHandler = e => {
      const key = e.key.toLowerCase();
      if (this.heldKeys.has(key)) { this.heldKeys.delete(key); if (this.mode === 'playing') e.preventDefault(); }
    };
    try {
      this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
      this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
      this.renderer.outputColorSpace = THREE.SRGBColorSpace;
      this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
      this.renderer.toneMappingExposure = 1.25;
      this.renderer.shadowMap.enabled = true;
      this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
      this.renderer.domElement.style.cssText = 'display:block;width:100%;height:100%;touch-action:none;';
      this.renderer.domElement.setAttribute('aria-label', 'SHASHKA 3D poyga maydoni');
      element.appendChild(this.renderer.domElement);
      this.buildWorld();
      this.observer = new ResizeObserver(() => this.resize());
      this.observer.observe(element);
      window.addEventListener('keydown', this.keyHandler);
      window.addEventListener('keyup', this.keyUpHandler);
      window.addEventListener('blur', this.blurHandler);
      this.resize();
      this.frame = requestAnimationFrame(t => this.loop(t));
      this.callbacks.onReady?.();
    } catch (error) {
      this.callbacks.onError?.(error instanceof Error ? error : new Error('WebGL ishlamadi'));
    }
  }

          material(color                           , roughness = 0.55) {
    return new THREE.MeshStandardMaterial({ color, roughness, metalness: 0.3 });
  }
          box(w        , h        , d        , material                , x = 0, y = 0, z = 0)             {
    const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), material);
    m.position.set(x, y, z);
    return m;
  }
          glow(color        ) {
    return new THREE.MeshBasicMaterial({ color, toneMapped: false });
  }
          buildWorld() {
    this.scene.background = new THREE.Color('#101c30');
    this.scene.fog = new THREE.FogExp2('#162037', 0.0075);
    const pmrem = new THREE.PMREMGenerator(this.renderer);
    const room = new RoomEnvironment();
    this.environmentTarget = pmrem.fromScene(room, 0.04);
    this.scene.environment = this.environmentTarget.texture;
    this.scene.environmentIntensity = 0.7;
    room.dispose(); pmrem.dispose();
    const skyCanvas = document.createElement('canvas');
    skyCanvas.width = 4; skyCanvas.height = 512;
    const skyCtx = skyCanvas.getContext('2d') ;
    const gradient = skyCtx.createLinearGradient(0, 0, 0, 512);
    gradient.addColorStop(0, '#050b19'); gradient.addColorStop(0.45, '#15243c');
    gradient.addColorStop(0.72, '#705061'); gradient.addColorStop(1, '#d49a84');
    skyCtx.fillStyle = gradient; skyCtx.fillRect(0, 0, 4, 512);
    const sky = new THREE.Mesh(new THREE.SphereGeometry(550, 32, 16),
      new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(skyCanvas), side: THREE.BackSide, fog: false }));
    this.scene.add(sky);
    this.scene.add(new THREE.HemisphereLight('#c4daff', '#323c4b', 2.2));
    const moon = new THREE.DirectionalLight('#c9e1ff', 3.5);
    moon.position.set(-25, 40, -30); moon.castShadow = true;
    moon.shadow.mapSize.set(1024, 1024);
    moon.shadow.camera.left = -20; moon.shadow.camera.right = 20;
    moon.shadow.camera.top = 30; moon.shadow.camera.bottom = -30;
    moon.shadow.camera.near = 1; moon.shadow.camera.far = 120;
    moon.shadow.bias = -0.001;
    this.scene.add(moon);
    const rim = new THREE.DirectionalLight('#ffa679', 2);
    rim.position.set(35, 8, 15); this.scene.add(rim);
    const roadMat = this.material('#27333d', 0.45);
    const asphalt = document.createElement('canvas'); asphalt.width = asphalt.height = 128;
    const ctx = asphalt.getContext('2d') ;
    const data = ctx.createImageData(128, 128);
    for (let p = 0; p < data.data.length; p += 4) {
      const n = 37 + Math.random() * 24;
      data.data[p] = n; data.data[p + 1] = n + 6; data.data[p + 2] = n + 11; data.data[p + 3] = 255;
    }
    ctx.putImageData(data, 0, 0);
    const tex = new THREE.CanvasTexture(asphalt); tex.wrapS = tex.wrapT = THREE.RepeatWrapping; tex.repeat.set(12, 300);
    roadMat.map = tex;
    const road = this.box(18.4, 0.18, 650, roadMat, 0, -0.13, -240);
    road.receiveShadow = true; this.scene.add(road);
    this.scene.add(this.box(800, 0.1, 700, this.material('#12202b'), 0, -0.4, -200));
    const lineMat = this.glow('#cad8db');
    for (const x of [-3.5, 0, 3.5]) {
      const dashes = new THREE.InstancedMesh(new THREE.BoxGeometry(0.12, 0.015, 3.5), lineMat, 72);
      dashes.userData.x = x;
      for (let i = 0; i < 72; i++) {
        dashes.setMatrixAt(i, new THREE.Matrix4().makeTranslation(x, 0.02, -i * 8 + 20));
      }
      dashes.frustumCulled = false;
      this.stripeMeshes.push(dashes); this.scene.add(dashes);
    }
    for (const side of [-1, 1]) {
      this.scene.add(this.box(0.12, 0.015, 650, this.glow('#dcbf78'), side * 7.9, 0.025, -240));
      this.scene.add(this.box(0.16, 0.13, 650, this.glow('#83dfe5'), side * 9.5, 0.45, -240));
      this.scene.add(this.box(0.2, 0.3, 650, this.material('#6a8290'), side * 9.5, 0.75, -240));
      this.scene.add(this.box(1.2, 0.8, 650, this.material('#3b4855'), side * 10.2, 0.3, -240));
      for (let i = 0; i < 20; i++) {
        const lamp = new THREE.Group();
        lamp.add(this.box(0.15, 8, 0.15, this.material('#607581'), side * 10.8, 4));
        lamp.add(this.box(4.2, 0.15, 0.15, this.material('#607581'), side * 8.8, 8));
        lamp.add(this.box(1.5, 0.07, 0.4, this.glow('#bde9ff'), side * 7.9, 7.9));
        const spotPatch = new THREE.Mesh(new THREE.PlaneGeometry(8, 10),
          new THREE.MeshBasicMaterial({ color: '#a3d4ed', transparent: true, opacity: 0.025, depthWrite: false }));
        spotPatch.rotation.x = -Math.PI / 2; spotPatch.position.set(side * 5, 0.035, 0);
        lamp.add(spotPatch);
        lamp.position.z = -i * 30 + 30; lamp.userData.origin = lamp.position.z;
        this.roadside.push(lamp); this.scene.add(lamp);
      }
    }
    this.makeCity();
    const sign = new THREE.Group();
    const pole = this.material('#5c7180');
    sign.add(this.box(0.25, 12, 0.25, pole, -10.5, 6));
    sign.add(this.box(0.25, 12, 0.25, pole, 10.5, 6));
    sign.add(this.box(21.5, 0.3, 0.3, pole, 0, 11.9));
    const signCanvas = document.createElement('canvas'); signCanvas.width = 1024; signCanvas.height = 192;
    const sc = signCanvas.getContext('2d') ;
    sc.fillStyle = '#12423f'; sc.fillRect(0, 0, 1024, 192);
    sc.strokeStyle = '#b6d4cb'; sc.lineWidth = 6; sc.strokeRect(10, 10, 1004, 172);
    sc.fillStyle = '#e0f3e8'; sc.font = 'bold 44px sans-serif'; sc.textAlign = 'center';
    sc.fillText('TOSHKENT   •   MAGISTRAL', 512, 75);
    sc.font = '30px sans-serif'; sc.fillText('↓          ↓          ↓          ↓', 512, 145);
    const signFace = new THREE.Mesh(new THREE.PlaneGeometry(14, 2.5), new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(signCanvas) }));
    signFace.position.set(0, 9.7, 0.2); sign.add(signFace); sign.position.z = -125;
    this.scene.add(sign);
    this.player = this.makeCar('#dfff00', this.playerModel, true);
    this.player.position.set(LANES[1], 0.08, 0);
    this.scene.add(this.player);
    this.addPlayerLights('#dfff00');
    this.camera.position.set(-10, 6.5, 14.2);
    this.camera.lookAt(-1.2, 0.5, -19);
    this.syncTraffic();
  }

          makeCity() {
    const windowCanvas = document.createElement('canvas'); windowCanvas.width = 64; windowCanvas.height = 128;
    const ctx = windowCanvas.getContext('2d') ;
    ctx.fillStyle = '#1b2d40'; ctx.fillRect(0, 0, 64, 128);
    for (let x = 4; x < 64; x += 10) for (let y = 4; y < 128; y += 12) {
      ctx.fillStyle = Math.random() < 0.45 ? '#638998' : Math.random() < 0.3 ? '#a29478' : '#263c4f';
      ctx.fillRect(x, y, 4, 5);
    }
    const windows = new THREE.CanvasTexture(windowCanvas); windows.wrapS = windows.wrapT = THREE.RepeatWrapping;
    const buildingMat = new THREE.MeshStandardMaterial({ map: windows, emissiveMap: windows, emissive: '#97b3c7', emissiveIntensity: 0.16, color: '#a5becb', roughness: 0.9 });
    for (const side of [-1, 1]) for (let i = 0; i < 38; i++) {
      const height = 14 + Math.random() * 76;
      const width = 8 + Math.random() * 15;
      const building = this.box(width, height, 12 + Math.random() * 15, buildingMat,
        side * (23 + Math.random() * 80), height / 2 - 1, -i * 15 + 20);
      this.scene.add(building);
      if (i % 8 === 0) this.scene.add(this.box(width + 0.1, 0.18, 16, this.glow(side === 1 ? '#405e91' : '#557d91'), building.position.x, height - 0.8, building.position.z));
    }
  }

          makeCar(color        , kind        , isPlayer = false)              {
    const car = new THREE.Group();
    const model = buildVehicle({ model: kind, color, isPlayer, paintMaterial: isPlayer ? this.playerPaint : undefined });
    car.name = model.name;
    car.userData.model = kind;
    car.userData.color = color;
    car.userData.brakeMaterial = model.userData.brakeMaterial;
    car.userData.wheels = model.userData.wheels;
    car.add(model);
    const spec = VEHICLE_MODELS[kind] ?? VEHICLE_MODELS[1];
    const shadowCanvas = document.createElement('canvas'); shadowCanvas.width = shadowCanvas.height = 64;
    const sc = shadowCanvas.getContext('2d') ;
    const radial = sc.createRadialGradient(32, 32, 4, 32, 32, 32);
    radial.addColorStop(0, 'rgba(0,0,0,.75)'); radial.addColorStop(1, 'rgba(0,0,0,0)');
    sc.fillStyle = radial; sc.fillRect(0, 0, 64, 64);
    const shadow = new THREE.Mesh(new THREE.PlaneGeometry(isPlayer ? 3.3 : spec.width + 1.1, isPlayer ? 6.3 : spec.length + 1.8),
      new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(shadowCanvas), transparent: true, depthWrite: false }));
    shadow.rotation.x = -Math.PI / 2; shadow.position.y = 0.04; car.add(shadow);
    this.mergeCarMeshes(car);
    return car;
  }

          mergeCarMeshes(car             ) {
    // Merge the stationary body pieces by finish: roughly 9 draws per car,
    // instead of dozens of individual body panels and wheel parts on phones.
    car.updateMatrixWorld(true);
    const groups = new Map                                                                                         ();
    const originals               = [];
    car.traverse(node => {
      if (!(node instanceof THREE.Mesh) || Array.isArray(node.material)) return;
      let parent                        = node.parent;
      while (parent && parent !== car) { if (parent.userData.isWheel) return; parent = parent.parent; }
      const m = node.material                              ;
      const key = [m === this.playerPaint ? 'player' : m.type, m.color?.getHex(), m.roughness, m.metalness,
        m.transparent, m.opacity, m.map?.uuid, m.toneMapped].join(':');
      if (!groups.has(key)) groups.set(key, { material: m, geometry: [], shadow: false });
      const target = groups.get(key) ;
      const geometry = node.geometry.index ? node.geometry.toNonIndexed() : node.geometry.clone();
      target.geometry.push(geometry.applyMatrix4(node.matrixWorld));
      target.shadow ||= node.castShadow;
      originals.push(node);
    });
    for (const node of originals) { node.parent?.remove(node); node.geometry.dispose(); }
    for (const { material, geometry, shadow } of groups.values()) {
      const merged = mergeGeometries(geometry, false);
      if (merged) { const mesh = new THREE.Mesh(merged, material); mesh.castShadow = shadow; car.add(mesh); }
      for (const geo of geometry) geo.dispose();
    }
  }
          syncTraffic() {
    const current = new Set        ();
    for (const car of this.state.traffic) {
      current.add(car.id);
      let model = this.trafficModels.get(car.id);
      if (model && (model.userData.model !== car.kind || model.userData.color !== PALETTE[car.color])) {
        this.scene.remove(model); this.disposeGroup(model); this.trafficModels.delete(car.id); model = undefined;
      }
      if (!model) {
        model = this.makeCar(PALETTE[car.color], car.kind);
        this.trafficModels.set(car.id, model); this.scene.add(model);
      }
      const lateralMotion = car.x - model.position.x;
      model.rotation.y = THREE.MathUtils.lerp(model.rotation.y, clamp(-lateralMotion * 4, -0.15, 0.15), 0.18);
      model.position.set(car.x, 0.04, car.z);
    }
    for (const [id, model] of this.trafficModels) if (!current.has(id)) {
      this.scene.remove(model); this.disposeGroup(model); this.trafficModels.delete(id);
    }
  }
          addPlayerLights(color        ) {
    const underlight = new THREE.PointLight(color, 1.8, 4.5);
    underlight.name = 'underglow'; underlight.position.set(0, 0.4, 0); this.player.add(underlight);
    const brakeLight = new THREE.PointLight('#ff1812', 0.25, 5.5);
    brakeLight.position.set(0, 0.65, 2.1); this.player.add(brakeLight);
    this.player.userData.brakeLight = brakeLight;
    const headlight = new THREE.SpotLight('#d7eaff', 38, 70, 0.4, 0.65, 1.1);
    headlight.position.set(0, 0.68, -2.12);
    const target = new THREE.Object3D(); target.position.set(0, 0.1, -42);
    this.player.add(target); headlight.target = target; this.player.add(headlight);
  }
          animateVehicle(model             , metres        , steering        , braking         ) {
    const wheels = model.userData.wheels                             ;
    for (const wheel of wheels ?? []) {
      const radius = wheel.userData.radius * Math.abs(wheel.parent?.scale.y ?? 1);
      wheel.rotation.x = (wheel.rotation.x - metres / Math.max(0.1, radius)) % (Math.PI * 2);
      wheel.rotation.y = THREE.MathUtils.lerp(wheel.rotation.y, wheel.userData.front ? steering : 0, 0.2);
    }
    const brakeMaterial = model.userData.brakeMaterial                                          ;
    if (brakeMaterial) brakeMaterial.emissiveIntensity = braking ? 3.4 : 0.65;
    const brakeLight = model.userData.brakeLight                                ;
    if (brakeLight) brakeLight.intensity = braking ? 2.4 : 0.25;
  }
          disposeGroup(group                ) {
    group.traverse(node => {
      if (node instanceof THREE.Mesh) {
        node.geometry.dispose();
        const mats = Array.isArray(node.material) ? node.material : [node.material];
        for (const mat of mats) {
          if (mat === this.playerPaint) continue;
          for (const value of Object.values(mat)) if (value instanceof THREE.Texture) value.dispose();
          mat.dispose();
        }
      }
    });
  }
          resize() {
    if (!this.renderer || this.disposed) return;
    const w = Math.max(1, this.root.clientWidth), h = Math.max(1, this.root.clientHeight);
    this.renderer.setSize(w, h, false); this.camera.aspect = w / h;
    this.camera.fov = w / h < 0.9 ? 65 : 54;
    this.camera.updateProjectionMatrix();
  }
          getStats()            {
    const { distance, speed, elapsed, nearMisses, combo, level, speedLimit, throttle, brake, acceleration } = this.state;
    return { distance, speed, elapsed, nearMisses, combo, level, speedLimit, throttle, brake, acceleration };
  }
          loop(time        ) {
    if (this.disposed) return;
    this.frame = requestAnimationFrame(t => this.loop(t));
    const dt = this.last ? clamp((time - this.last) / 1000, 0, 0.1) : 0;
    this.last = time;
    if (this.mode === 'playing') {
      this.accumulator += dt;
      while (this.accumulator >= 1 / 60 && this.mode === 'playing') {
        const direction = this.queue.shift() ?? 0;
        const throttle = this.touchPedals.throttle || this.heldKeys.has('w') || this.heldKeys.has('arrowup');
        const brake = this.touchPedals.brake || this.heldKeys.has('s') || this.heldKeys.has('arrowdown') || this.heldKeys.has(' ');
        const input           = { tick: this.state.ticks };
        if (direction) input.direction = direction;
        if (throttle !== this.state.throttle) input.throttle = throttle;
        if (brake !== this.state.brake) input.brake = brake;
        if (Object.keys(input).length > 1) this.inputs.push(input);
        stepSimulation(this.state, direction, { throttle, brake });
        this.accumulator -= 1 / 60;
        if (this.state.crashed || this.state.ticks >= MAX_TICKS) {
          this.mode = 'crashed'; this.hitAt = time;
          this.clearControls();
          this.callbacks.onTick?.(this.getStats());
          this.callbacks.onCrash?.({ ...this.getStats(), inputs: [...this.inputs], ticks: this.state.ticks });
          this.crashSound();
        }
      }
      this.visualDistance = this.state.distance;
      this.player.position.x += (this.state.x - this.player.position.x) * Math.min(1, dt * 18);
      this.player.rotation.z = THREE.MathUtils.lerp(this.player.rotation.z, (LANES[this.state.lane] - this.state.x) * -0.035, dt * 12);
      this.player.rotation.y = THREE.MathUtils.lerp(this.player.rotation.y, (LANES[this.state.lane] - this.state.x) * -0.035, dt * 12);
      this.player.rotation.x = THREE.MathUtils.lerp(this.player.rotation.x, clamp(this.state.acceleration / 42, -1, 1) * 0.045, dt * 5);
      this.player.position.y = 0.08 + Math.sin(this.state.distance * 2.1) * Math.min(0.014, this.state.speed / 14000);
      this.animateVehicle(this.player, this.state.speed / 3.6 * dt, (LANES[this.state.lane] - this.state.x) * -0.035, this.state.brake);
      this.syncTraffic();
      for (const car of this.state.traffic) {
        const model = this.trafficModels.get(car.id);
        if (model) this.animateVehicle(model, car.speed / 3.6 * dt, 0, false);
      }
      if (time - this.lastUi > 100) { this.callbacks.onTick?.(this.getStats()); this.lastUi = time; }
    } else if (this.mode === 'attract') {
      this.attractTime += dt;
      this.visualDistance += dt * 6;
      this.player.position.y = 0.08 + Math.sin(time * 0.002) * 0.012;
      this.animateVehicle(this.player, dt * 6, 0, false);
      for (const [id, model] of this.trafficModels) {
        const source = this.state.traffic.find(c => c.id === id);
        if (source) model.position.z = source.z + Math.sin(time * 0.00018 + id) * 5;
        this.animateVehicle(model, dt * 6, 0, false);
      }
    }
    const stripeMatrix = new THREE.Matrix4();
    for (const stripe of this.stripeMeshes) {
      for (let i = 0; i < 72; i++) {
        const z = (((-i * 8 + 20) + this.visualDistance) % 576 + 576) % 576 - 550;
        stripe.setMatrixAt(i, stripeMatrix.makeTranslation(stripe.userData.x, 0.02, z));
      }
      stripe.instanceMatrix.needsUpdate = true;
    }
    for (const lamp of this.roadside) lamp.position.z = ((lamp.userData.origin + this.visualDistance) % 600 + 600) % 600 - 560;
    const playing = this.mode !== 'attract';
    const narrow = this.camera.aspect < 0.9;
    const cameraX = playing ? this.player.position.x * 0.35 : (narrow ? -1.75 : -10);
    const target = new THREE.Vector3(cameraX, playing ? 6 : 6.5, playing ? 12 : 14.2);
    this.camera.position.lerp(target, dt * 3);
    this.camera.lookAt(playing ? this.player.position.x * 0.25 : -1.2, 0.6, playing ? -23 : -19);
    if (this.mode === 'crashed' && time - this.hitAt < 450) {
      const shake = (1 - (time - this.hitAt) / 450) * 0.2;
      this.camera.position.x += Math.sin(time * 0.16) * shake;
    }
    if (this.engine && this.engineGain && this.sound) {
      this.engine.frequency.setTargetAtTime(this.mode === 'playing' ? 36 + this.state.speed * 0.55 + (this.state.throttle ? 18 : 0) : 35, this.sound.currentTime, 0.15);
      this.engineGain.gain.setTargetAtTime(!this.muted && this.mode === 'playing' ? (this.state.throttle ? 0.04 : 0.02) : 0, this.sound.currentTime, 0.1);
    }
    this.renderer.render(this.scene, this.camera);
  }

  setAppearance({ color, model }                                   ) {
    this.playerPaint.color.set(color);
    this.player?.traverse(node => { if (node instanceof THREE.PointLight && node.name === 'underglow') node.color.set(color); });
    const selected = typeof model === 'number' && VEHICLE_MODELS.some(item => item.id === model) ? model : this.playerModel;
    if (selected === this.playerModel || !this.player) return;
    this.playerModel = selected;
    const position = this.player.position.clone(), rotation = this.player.rotation.clone();
    this.scene.remove(this.player); this.disposeGroup(this.player);
    this.player = this.makeCar(color, selected, true);
    this.player.position.copy(position); this.player.rotation.copy(rotation);
    this.addPlayerLights(color);
    this.scene.add(this.player);
  }
  setQuality(quality                ) {
    if (!this.renderer) return;
    this.renderer.setPixelRatio(quality === 'high' ? Math.min(window.devicePixelRatio || 1, 1.75) : 1);
    this.renderer.shadowMap.enabled = quality === 'high'; this.resize();
  }
  setMuted(muted         ) { this.muted = muted; if (!muted) this.setupSound(); }
          setupSound() {
    try {
      if (!this.sound) {
        this.sound = new AudioContext(); this.engine = this.sound.createOscillator();
        this.engine.type = 'sawtooth'; this.engine.frequency.value = 50;
        const filter = this.sound.createBiquadFilter(); filter.type = 'lowpass'; filter.frequency.value = 200;
        this.engineGain = this.sound.createGain(); this.engineGain.gain.value = 0;
        this.engine.connect(filter); filter.connect(this.engineGain); this.engineGain.connect(this.sound.destination); this.engine.start();
      }
      void this.sound.resume();
    } catch { /* Audio is optional on mobile browsers. */ }
  }
          crashSound() {
    if (this.muted || !this.sound) return;
    const oscillator = this.sound.createOscillator(), gain = this.sound.createGain();
    oscillator.type = 'triangle'; oscillator.frequency.setValueAtTime(160, this.sound.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(25, this.sound.currentTime + 0.3);
    gain.gain.setValueAtTime(0.2, this.sound.currentTime); gain.gain.exponentialRampToValueAtTime(0.001, this.sound.currentTime + 0.35);
    oscillator.connect(gain); gain.connect(this.sound.destination); oscillator.start(); oscillator.stop(this.sound.currentTime + 0.35);
    oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
  }
  start(seed        ) {
    this.state = createSimulation(seed); this.inputs = []; this.queue = []; this.accumulator = 0;
    this.clearControls();
    this.last = 0; this.visualDistance = 0; this.mode = 'playing';
    this.player.position.set(this.state.x, 0.08, 0); this.player.rotation.set(0, 0, 0);
    this.syncTraffic(); this.callbacks.onTick?.(this.getStats());
    if (!this.muted) this.setupSound();
  }
  steer(direction        ) {
    if (this.mode === 'playing' && this.queue.length < 3) this.queue.push(direction);
  }
  setPedals({ throttle, brake }                                         ) {
    if (this.mode !== 'playing') return;
    if (typeof throttle === 'boolean') this.touchPedals.throttle = throttle;
    if (typeof brake === 'boolean') this.touchPedals.brake = brake;
  }
          clearControls() { this.touchPedals = { throttle: false, brake: false }; this.heldKeys.clear(); this.queue = []; }
  pause() { if (this.mode === 'playing') { this.mode = 'paused'; this.clearControls(); } }
  resume() { if (this.mode === 'paused') { this.clearControls(); this.mode = 'playing'; this.last = 0; this.accumulator = 0; } }
  reset() {
    this.state = createSimulation(476921); this.inputs = []; this.queue = []; this.mode = 'attract';
    this.clearControls();
    this.visualDistance = 0; this.last = 0; this.accumulator = 0;
    this.player.position.set(this.state.x, 0.08, 0); this.player.rotation.set(0, 0, 0); this.syncTraffic();
  }
  dispose() {
    this.disposed = true; cancelAnimationFrame(this.frame);
    window.removeEventListener('keydown', this.keyHandler); window.removeEventListener('keyup', this.keyUpHandler);
    window.removeEventListener('blur', this.blurHandler); this.observer?.disconnect();
    this.disposeGroup(this.scene); this.playerPaint.dispose(); this.environmentTarget?.dispose(); this.renderer?.dispose();
    this.renderer?.domElement.remove(); if (this.sound) void this.sound.close();
  }
}

return { RaceScene, VEHICLE_MODELS, SIMULATION_VERSION, MAX_SPEED, MAX_TICKS, LANES };
})(); return LOADED; }
