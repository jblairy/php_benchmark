# PHP Benchmark Suite

A modern benchmarking framework for PHP that allows testing performance of different implementations and evaluating performance evolution across PHP versions.

## Features

- **Performance Testing**: Automated benchmarks for different PHP aspects
- **Version Comparison**: Test performance across different PHP versions
- **Detailed Reports**: Formatted results with comprehensive statistics
- **Modular Architecture**: Easily add your own test cases

## Requirements

- docker
- docker-compose

## Installation

```bash
git clone https://github.com/jblairy/php-benchmark.git
cd php-benchmark
make up
```

## Usage
```bash
make run
```

Optionnal parameters:
- `test`: Run a specific benchmark
- `iterations`: Run a specific number of iterations

```bash
make run test=<TestName> iterations=<NumberYouLike>
```

## Contributing
Contributions are welcome! Here's how to contribute:

### Contribution Guidelines
- Follow PSR-12 coding standards
- Document your benchmarks
- Respect the existing structure

### Creating Custom Benchmarks
1. Create a class in `src/Benchmark/Case/`
2. Extend `AbstractBenchmark`
3. Add attribute or version-specific attributes `#[All]`
4. Implement your test method


## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Acknowledgments
- PHP Community for inspiration
- Project contributors
- Everyone who tests and reports issues

## Support
- **Issues**: [GitHub Issues](https://github.com/jblairy/php-benchmark/issues)
- **Discussions**: [GitHub Discussions](https://github.com/jblairy/php-benchmark/discussions)

⭐ If you like this project, please give it a star!

