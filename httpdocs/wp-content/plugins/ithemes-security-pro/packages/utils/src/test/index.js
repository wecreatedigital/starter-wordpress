import { makeUrlRelative, shortenNumber } from 'packages/utils/src';

describe( 'makeUrlRelative', () => {
	it( 'should return the relative url', () => {
		const relative = makeUrlRelative( 'https://security.test/', 'https://security.test/wp-json/' );

		expect( relative ).toEqual( '/wp-json/' );
	} );

	it( 'should return the relative url when base has a path', () => {
		const relative = makeUrlRelative( 'https://security.test/wp-json/', 'https://security.test/wp-json/wp/v2/' );
		expect( relative ).toEqual( '/wp/v2/' );
	} );
} );

describe( 'shortenNumber', () => {
	const cases = [
		[ 5, '5' ],
		[ 50, '50' ],
		[ 53, '53' ],
		[ 100, '100' ],
		[ 152, '152' ],
		[ 1000, '1k' ],
		[ 1005, '1k' ],
		[ 1025, '1k' ],
		[ 1125, '1.1k' ],
		[ 5232, '5.2k' ],
		[ 12000, '12k' ],
		[ 12345, '12k' ],
		[ 123456, '123k' ],
		[ 1000000, '1m' ],
		[ 1234567, '1.2m' ],
		[ 12345678, '12m' ],
		[ 123456789, '123m' ],
		[ 1234567890, '1.2b' ],
	];

	it.each( cases )( 'should convert %d to %s', ( number, shortened ) => {
		expect( shortenNumber( number ) ).toBe( shortened );
	} );
} );
